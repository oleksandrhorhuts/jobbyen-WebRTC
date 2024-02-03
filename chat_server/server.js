'use strict';

require('dotenv').config();

var fs = require('fs');
var https = require('https');
var http = require('http');
var mysql = require('mysql');
var express = require('express');
var dateFormat = require('dateformat');

var app = express();

var db = mysql.createConnection({
	host: 'localhost',
	user: 'phpmyadmin',
	password: 'mHZRlsSWXzPkk8vu7hLm',
	database: 'jobbyen'
})
db.connect(function (err) {
	console.log('connected mysql db');
	if (err) console.log(err)
})


var serverPort = 8443;
if (process.env.server_type == 1) {
	var options = {
		key: fs.readFileSync('./privkey.pem'),
		cert: fs.readFileSync('./fullchain.pem')
	};
	var server = https.createServer(options, app);
} else {
	var server = http.createServer(app);
}


var io = require('socket.io')(server);


app.get('/', function (req, res) {

	console.log('----------------');
	res.sendFile(__dirname + '/public/index.html');
});

var online_users = [];
var g_sockets = [];


setInterval(function () {
	db.query("SELECT * FROM seeker_agent_socket WHERE active = 1", (err, result) => {

		result.forEach(element => {

			for (var idx = 0; idx < g_sockets.length; idx++) {
				g_sockets[idx].broadcast.emit('send_notification_to_users_from_server', { agent_user_id: element.agent_user_id });
			}

			db.query("UPDATE seeker_agent_socket SET active = 0 WHERE agent_user_id = '" + element.agent_user_id + "'", (err, res) => {
			});
		});

	});
}, 1000);



setInterval(function () {
	var currentdate = new Date(new Date().getTime() + 5 * 60000).toLocaleString('en-US', { timeZone: 'Europe/Copenhagen' });
	var milliseconds = Math.floor(new Date().getTime() / 1000);
	var datetime = dateFormat(currentdate, "yyyy-mm-dd HH:MM:ss");
	var date = dateFormat(currentdate, "mm-dd");

	if (milliseconds % 60 == 0) {


		db.query("SELECT * FROM interviews WHERE interview_time <= '" + datetime + "' AND ready = 1", (err, result) => {

			result.forEach(element => {

				console.log(element.id);
				db.query("UPDATE interviews SET ready = 2 WHERE uuid = '" + element.uuid + "'", (err, res) => {

				});



				var obj = {
					employer_id: element.employer_id,
					seeker_id: element.seeker_id,
					uuid: element.uuid,
					app_id: element.app_id
				}
				console.log(g_sockets.length);


				for (var idx = 0; idx < g_sockets.length; idx++) {
					g_sockets[idx].broadcast.emit('time_to_interview', obj);
				}
			});
		});
	}
}, 1000);


var channels = {};
var sockets = {};

io.on('connection', function (socket) {
	socket.channels = {};
	sockets[socket.id] = socket;
	socket.on('join', function (config) {
		console.log('-----------join-----------');
		//console.log("[" + socket.id + "] join ", config);
		var channel = config.channel;
		var userdata = config.userdata;

		if (channel in socket.channels) {
			console.log("[" + socket.id + "] ERROR: already joined ", channel);
			return;
		}

		if (!(channel in channels)) {
			channels[channel] = {};
		}

		for (var id in channels[channel]) {
			channels[channel][id].socket.emit('addPeer', { 'peer_id': socket.id, 'should_create_offer': false, 'userdata': userdata });
			socket.emit('addPeer', { 'peer_id': id, 'should_create_offer': true, 'userdata': channels[channel][id].userdata });
		}

		var _data = {
			socket: socket,
			userdata: userdata
		}
		channels[channel][socket.id] = _data;
		socket.channels[channel] = channel;
	});
	function part(channel) {
		//console.log("[" + socket.id + "] part ");

		if (!(channel in socket.channels)) {
			//console.log("[" + socket.id + "] ERROR: not in ", channel);
			return;
		}

		var user_data = channels[channel][socket.id].userdata;

		delete socket.channels[channel];
		delete channels[channel][socket.id];



		for (var id in channels[channel]) {
			channels[channel][id].socket.emit('removePeer', { 'peer_id': socket.id, 'userdata': user_data });
			socket.emit('removePeer', { 'peer_id': id, 'userdata': user_data });
		}
	}
	socket.on('part', part);

	socket.on('relayICECandidate', function (config) {
		var peer_id = config.peer_id;
		var ice_candidate = config.ice_candidate;
		//console.log("[" + socket.id + "] relaying ICE candidate to [" + peer_id + "] ", ice_candidate);

		if (peer_id in sockets) {
			sockets[peer_id].emit('iceCandidate', { 'peer_id': socket.id, 'ice_candidate': ice_candidate });
		}
	});

	socket.on('relaySessionDescription', function (config) {
		var peer_id = config.peer_id;
		var session_description = config.session_description;
		//console.log("[" + socket.id + "] relaying session description to [" + peer_id + "] ", session_description);

		if (peer_id in sockets) {
			sockets[peer_id].emit('sessionDescription', { 'peer_id': socket.id, 'session_description': session_description });
		}
	});
	socket.on('joined', (obj) => {
		io.sockets.emit("user-joined", socket.id, io.engine.clientsCount, online_users);
	})
	socket.on('signal', (toId, message) => {
		io.to(toId).emit('signal', socket.id, message);
	});
	socket.on('init', (obj) => {


	})
	socket.on('chk_online_response', (obj) => {

	});
	socket.on('disconnect', () => {
		for (var channel in socket.channels) {
			part(channel);
		}
		//console.log("[" + socket.id + "] disconnected");
		delete sockets[socket.id];



	})
	g_sockets.push(socket);


	socket.on('login_user', function (data) {

		// if (users.indexOf(data) == -1){
		// 	users.push(data);
		//     console.log('log in users = ' + users);
		// }

		// socket.emit('own_all_user', users);
		// socket.broadcast.emit('all_user', users);
	})
	socket.on('logout_user', function (data) {
		// Decrease the socket count on a disconnect, emit
		// users.splice(users.indexOf(data), 1);
		// console.log('log out users = ' + users);
		// socket.emit('own_all_user', users);
		// socket.broadcast.emit('all_user', users);
	})
	socket.on('new_message', function (obj) {

	})
	socket.on('join-stream', (obj) => {
		socket.broadcast.emit('join-stream', obj);
	})


	socket.on('new_video_request', function (obj) {

	})
	socket.on('interview_response_to_employer', (obj) => {
		socket.broadcast.emit('interview_response_to_employer', obj);
	});
	socket.on('video_accept_request', function (obj) {

	})
	socket.on('typing_notification', (obj) => {
		socket.broadcast.emit('typing_notification', obj);
	});
	socket.on('chat_message_company', function (obj) {
		socket.broadcast.emit('chat_message_company', obj);
	});
	socket.on('chat_message', function (obj) {

		var is_attach = 0;
		var message = '';
		var parsed_obj = JSON.parse(obj);

		var is_attached = 0;
		if (parsed_obj.action == "interview_request") {
			message = parsed_obj.stringify_interview_time;
			is_attached = 2;
		}
		else if (parsed_obj.action == "message") {
			message = parsed_obj.message;
			is_attached = 0;
		}
		else if (parsed_obj.action == "doc") {
			message = parsed_obj.message;
			is_attached = 30;
		}
		else if (parsed_obj.action == "pdf") {
			message = parsed_obj.message;
			is_attached = 40;
		}
		else if (parsed_obj.action == "image") {
			message = parsed_obj.message;
			is_attached = 20;
		}
		else if (parsed_obj.action == "reject") {
			message = parsed_obj.message;
			is_attached = 70;
		}
		else if (parsed_obj.action == "pending") {
			message = parsed_obj.message;
			is_attached = 100;
		}
		else if (parsed_obj.action == "postpone-candidate") {
			message = parsed_obj.message;
			is_attached = 80;
		}
		else if (parsed_obj.action == "interview_request_postpone") {
			message = parsed_obj.stringify_interview_time;
			is_attached = 90;
		}
		else {
			is_attached = 1;
			message = parsed_obj.message;
		}
		console.log(obj);



		if (is_attached == 2 || is_attached == 90) {
			var sql = "DELETE FROM messages WHERE is_attach = 2";
			db.query(sql, function (err, result) {
				if (err) throw err;

				db.query("INSERT INTO messages (`sender_id`, `receiver_id`, `text`, `is_seen`, `created_at`, `updated_at`, `is_attach`, `uuid`) VALUES ('" + parsed_obj.userid + "','" + parsed_obj.receiver + "', '" + message + "', '0', '" + getCurrentDateTime() + "', '" + getCurrentDateTime() + "', '" + is_attached + "', '" + parsed_obj.uuid + "')", function (err, result) {
					console.log(err);
				});

			});
		}
		else {
			db.query("INSERT INTO messages (`sender_id`, `receiver_id`, `text`, `is_seen`, `created_at`, `updated_at`, `is_attach`, `uuid`) VALUES ('" + parsed_obj.userid + "','" + parsed_obj.receiver + "', '" + message + "', '0', '" + getCurrentDateTime() + "', '" + getCurrentDateTime() + "', '" + is_attached + "', '" + parsed_obj.uuid + "')", function (err, result) {
				console.log(err);
			});
		}


		db.query("SELECT * FROM contact WHERE contact_self_id = '" + parsed_obj.userid + "' AND contact_users = '" + parsed_obj.receiver + "' AND contact_type = '0'", function (err, result) {

			if (err) throw err;
			console.log(result.length);
			if (result.length < 1) {
				db.query("INSERT INTO contact(`contact_self_id`, `contact_users`, `contact_type`, `unique_contact`) VALUES ('" + parsed_obj.userid + "', '" + parsed_obj.receiver + "', '0', '" + parsed_obj.uuid + "000')", function (err, result) {
				});
			}

		});

		db.query("SELECT * FROM contact WHERE contact_self_id = '" + parsed_obj.receiver + "' AND contact_users = '" + parsed_obj.userid + "' AND contact_type = '0'", function (err, result) {

			if (err) throw err;
			console.log(result.length);
			if (result.length < 1) {
				db.query("INSERT INTO contact(`contact_self_id`, `contact_users`, `contact_type`, `unique_contact`) VALUES ('" + parsed_obj.receiver + "', '" + parsed_obj.userid + "', '0', '" + parsed_obj.uuid + "000')", function (err, result) {
				});
			}

		});
		if (parsed_obj.action == "message" || parsed_obj.action == "doc" || parsed_obj.action == "pdf" || parsed_obj.action == "image" || parsed_obj.action == "reject" || parsed_obj.action == "pending" || parsed_obj.action == "postpone-candidate" || parsed_obj.action == "interview_request_postpone") {
			db.query("INSERT INTO notifications (`user_id`, `name`, `sender`, `type`, `created_at`, `updated_at`) VALUES ('" + parsed_obj.receiver + "','" + parsed_obj.userid + "', '0', 3, '" + getCurrentDateTime() + "', '" + getCurrentDateTime() + "')", function (err, result) {
				console.log(err);
			});
		}

		if (parsed_obj.action == "interview_request") {
			db.query("INSERT INTO notifications (`user_id`, `name`, `sender`, `type`, `created_at`, `updated_at`) VALUES ('" + parsed_obj.receiver + "','" + parsed_obj.userid + "', '0', 4, '" + getCurrentDateTime() + "', '" + getCurrentDateTime() + "')", function (err, result) {
				console.log(err);
			});
		}

		socket.broadcast.emit('chat_message', obj);
	})

	socket.on('sent_application', function (obj) {
		socket.broadcast.emit('sent_application', obj);
	})
	socket.on('send_notification_to_users', function (obj) {
		socket.broadcast.emit('send_notification_to_users', obj);
	})
	socket.on('send_notification_to_user', function (obj) {
		socket.broadcast.emit('send_notification_to_user', obj);
	})

	socket.on('message', function (obj) {
		socket.broadcast.emit('message', obj);
	})
	socket.on('visited_user', function (obj) {
		socket.broadcast.emit('visited_user', obj);
	})
	socket.on('endCall', function (obj) {
		socket.broadcast.emit('endCall', obj);
	})
	socket.on('main_screenshare', function (obj) {
		socket.broadcast.emit('main_screenshare', obj);
	})
	socket.on('interview_box_add', function (obj) {

		console.log(obj);
		socket.broadcast.emit('interview_box_add', obj);
	})

	socket.on('end_call_from_seeker', function (obj) {
		socket.broadcast.emit('end_call_from_seeker', obj);
	});

	socket.on('join_my_video', function (obj) {

		var uuid = obj.identify;

		db.query("UPDATE interviews SET ready = 2 WHERE uuid = '" + uuid + "'", (err, res) => {

		});
		socket.broadcast.emit('join_my_video', obj);
	})


});

function getCurrentDateTime() {
	var currentDate = new Date().toLocaleString('en-US', { timeZone: 'Europe/Paris' });
	var currentYear = parseInt(dateFormat(currentDate, 'yyyy'));
	var currentMonth = 0;
	var currentDay = 0;
	var currentHour = 0;
	var currentMinute = 0;
	var currentSecond = 0;

	if (parseInt(dateFormat(currentDate, 'mm')) < 10) {
		currentMonth = "0" + parseInt(dateFormat(currentDate, 'mm'));
	}
	else {
		currentMonth = parseInt(dateFormat(currentDate, 'mm'));
	}

	if (parseInt(dateFormat(currentDate, 'dd')) < 10) {
		currentDay = "0" + parseInt(dateFormat(currentDate, 'dd'));
	}
	else {
		currentDay = parseInt(dateFormat(currentDate, 'dd'));
	}


	if (parseInt(dateFormat(currentDate, 'HH')) < 10) {
		currentHour = "0" + parseInt(dateFormat(currentDate, 'HH'))
	}
	else {
		currentHour = parseInt(dateFormat(currentDate, 'HH'));
	}

	if (parseInt(dateFormat(currentDate, 'MM')) < 10) {
		currentMinute = "0" + parseInt(dateFormat(currentDate, 'MM'));
	}
	else {
		currentMinute = parseInt(dateFormat(currentDate, 'MM'))
	}
	if (parseInt(dateFormat(currentDate, 'ss')) < 10) {
		currentSecond = "0" + parseInt(dateFormat(currentDate, 'ss'));
	}
	else {
		currentSecond = parseInt(dateFormat(currentDate, 'ss'));
	}
	var datetime = currentYear + "-" + currentMonth + "-" + currentDay + " " + currentHour + ":" + currentMinute + ":" + currentSecond;
	return datetime;
}
server.listen(serverPort, function () {
	console.log('server up and running at %s port', serverPort);
});
