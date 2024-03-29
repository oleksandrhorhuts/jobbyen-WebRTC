'use strict'


//The typical utilities required for having things working
const fs = require('fs');
const https = require('https');
const http = require('http');
const path = require('path');
const randomstring = require('randomstring');
const express = require('express');
const puppeteer = require('puppeteer');
var FfmpegCommand = require('fluent-ffmpeg');
const { exec } = require('child_process');

//Load configuration from .env config file
require('dotenv').load();

//Import Twilio client library
const Twilio = require('twilio');
const { Socket } = require('dgram');

//prepareCleanTermination()

//Load launch options from command line
var protocol = process.argv[3];
if (!protocol || (protocol != 'http' && protocol != 'https')) {
  protocol = 'http';
}

var port = 5000;

//Set up our web server
var app = express();
var publicpath = path.join(__dirname, "./public");
app.use("/", express.static(publicpath));

var server;

var httpsOptions = {
	key: fs.readFileSync('./key.pem'),
	cert: fs.readFileSync('./cert.pem')
};
server = https.createServer(httpsOptions, app);

server.listen(port, function () {
  console.log("Express server listening for " + protocol + " on *:" + port);
});

var io = require('socket.io')(server);

/*********************************************************************
INTERESTING STUFF STARTS BELOW THIS LINE
**********************************************************************/

const ACCOUNT_SID = process.env.ACCOUNT_SID; //Get yours here: https://www.twilio.com/console
const API_KEY_SID = process.env.API_KEY_SID; //Get yours here: https://www.twilio.com/console/video/dev-tools/api-keys
const API_KEY_SECRET = process.env.API_KEY_SECRET; //Get yours here: https://www.twilio.com/console/video/dev-tools/api-keys

const client = new Twilio(API_KEY_SID, API_KEY_SECRET, {
  accountSid: ACCOUNT_SID
});

var roomSid;
var roomName = process.argv[2];
if (!roomName) {
  roomName = randomstring.generate(10);
}







// client.video.rooms
// .create({
//   type: 'group',
//   uniqueName: roomName,
//   recordParticipantsOnConnect: false
// })
// .then(room => {
//   roomSid = room.sid;
//   console.log('Room ' + room.uniqueName + ' created successfully');
//   console.log('RoomSid=' + room.sid);
//   console.log('Room ' + roomName + ' ready to receive client connections');
// })
// .catch(error => {
//   console.log('Error creating room ' + error);
//   process.exit(-1);
// });

//AccessToken management
//Twilio's utilities for having AccessTokens working.
var AccessToken = Twilio.jwt.AccessToken;
var VideoGrant = AccessToken.VideoGrant;

var rooms = [];
var tracks = [];
var layout = 0;

var videoCommands = [];
function makeid(length) {
  var result = '';
  var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  var charactersLength = characters.length;
  for (var i = 0; i < length; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }
  return result;
}
function record_stop_room(room_id) {
  var exist_command = videoCommands.find(x => x.room_id == room_id);
  if (exist_command) {
    console.log('-kill');
    exist_command.command.kill("SIGKILL");
    videoCommands.splice(videoCommands.indexOf(exist_command), 1);
  }
}
async function record_room(room_id) {
  try {

    console.log(room_id);
    console.log('start puppetter');
    const browser = (await puppeteer.launch({
      executablePath: '/usr/bin/google-chrome', args: [
        '--no-sandbox',
        '--window-size=1920,1080',
        '--use-fake-ui-for-media-stream=1',
        '--use-fake-device-for-media-stream=1',
        '--allow-file-access=1',
        '--enable-usermedia-screen-capturing',
        '--allow-http-screen-capture',
        '--auto-select-desktop-capture-source=pickme',
        '--disable-setuid-sandbox',
      ], headless: false
    }));
    const Page = (await browser.newPage());
    var viewport = { width: 1920, height: 1080 }
    var display = ':0.0+0,0'
    await Page.setViewport(viewport);
    await Page.goto('https://jobbyen.dk:3000/#!/record/' + room_id);
    Page.on('console', msg => console.log(msg));

    var videoCommand = new FfmpegCommand({ logger: "debug" });

    videoCommand
      .addInput(':0.0+0,0')
      .addInputOptions('-y', '-f', 'x11grab', '-draw_mouse', '0', '-s', '1920x1080')
      .aspect('16:9')
      .withFpsInput(60)
      .withFpsOutput(60)
      // .autopad(process.argv[9])
      .applyAutopadding(true, 'black')
      .output('record_' + room_id + '_' + makeid(10) + '.mp4')
      .on('error', function (err, stdout, stderr) {
        console.log(err);
      })
      .on('end', async function (stdout, stderr) {
        console.log('Finished processing');
        await Page.close()
        await browser.close()
      })
    console.log('---start---');
    videoCommand.withSize('1920x1080').outputOptions(['-c:v libx264', '-movflags +faststart', '-pix_fmt yuv420p', '-crf 0', '-preset ultrafast'])
    videoCommands.push({ room_id: room_id, command: videoCommand });
    videoCommand.run();
    console.log('-----done----');
    return;


  } catch (e) {
    console.log(e);
  }
}

var fileStreams = [];
io.on('connection', function (socket) {

  //Client ask for an AccessToken. Generate a random identity and provide it.
  socket.on('getAccessToken', function (msg) {

    console.log("getAccessToken request received");
    console.log(msg);

    var userName;
    if (msg && msg.userName) {
      userName = msg.userName;
    } else {
      userName = randomstring.generate(20);
    }


    var exist_room = rooms.find(x => x.session_link == msg.session_link);
    if (exist_room) {
      roomName = exist_room.title;
    } else {
      return;
    }

    console.log(userName);
    var accessToken = new AccessToken(
      ACCOUNT_SID,
      API_KEY_SID,
      API_KEY_SECRET
    );

    accessToken.identity = userName;

    var grant = new VideoGrant();
    grant.room = roomName;
    accessToken.addGrant(grant);

    var answer = {
      jwtToken: accessToken.toJwt(),
      roomName: roomName
    }

    console.log("JWT accessToken generated: " + accessToken.toJwt() + "\n");

    socket.emit("accessToken", answer);
  });
  var meeting_started = 0;
  socket.on('meeting_status', (obj) => {
    socket.emit('meeting_status', { meeting_status: meeting_started });
  })
  socket.on('recording_status', (obj) => {
    console.log('-recording-status asking---');
    var _room = rooms.find(x => x.session_link == obj.session_link);
    if (_room) {
      socket.emit('recording_status', { session_link: obj.session_link, recording_status: _room.recording_started });
    }
  })

  socket.on('selected_track', (obj) => {
    var _room = rooms.find(x => x.session_link == obj.session_link);
    if (_room) {
      _room.tracks = obj.track_id_arr;
      _room.meeting_started = 1;
    }
    socket.broadcast.emit('selected_track', { session_link: obj.session_link, tracks: obj.track_id_arr });
  });

  socket.on('record_started', (obj) => {
    //record_room(obj.session_link);
    var _room = rooms.find(x => x.session_link == obj.session_link);
    if (_room) {
      _room.recording_started = 1;
      console.log(_room);
    }

    socket.broadcast.emit('record_started', obj);
  })
  socket.on('room_history', (obj) => {
    var data = fs.readFileSync('public/' + obj.session_link + '/' + obj.session_link + '_history.json');



    fs.readFile('public/' + obj.session_link + '/' + obj.session_link + '_history.json', 'utf8', function readFileCallback(err, data) {
      if (err) {
        console.log(err);
      } else {
        var _obj = JSON.parse(data); //now it an object
        console.log(_obj);
        _obj.table.push({
          track_id_arr: obj.track_id_arr,
          layout: obj.layout,
          time: obj.time
        }); //add some data
        var json = JSON.stringify(_obj); //convert it back to json
        fs.writeFile('public/' + obj.session_link + '/' + obj.session_link + '_history.json', json, 'utf8', function (err) {
          if (err) {
            console.log("An error occured while writing JSON Object to File.");
            return console.log(err);
          }

          console.log("JSON file has been saved.");
        });
      }
    });


  })
  socket.on('emulate', (obj) => {
    fs.readFile('public/' + obj.session_link + '/' + obj.session_link + '_history.json', 'utf8', function readFileCallback(err, data) {
      if (err) {
        console.log(err);
      } else {
        var _obj = JSON.parse(data); //now it an object
        var time_idx = 0;
        var _myInterval = setInterval(() => {
          var _emulate_item = _obj.table.find(x => x.time == time_idx);
          console.log(_emulate_item);
          if (_emulate_item) {
            socket.emit('send_emulate', { item: _emulate_item });
          } else {
            clearInterval(_myInterval);
          }

          time_idx++;
        }, 1000);

      }
    });
  })

  socket.on('record_stop', (obj) => {
    //record_stop_room(obj.session_link);
    for (var idx = 0; idx < fileStreams.length; idx++) {
      fileStreams[idx].file_stream.end();
    }
    socket.broadcast.emit('record_stop', obj);

  })
  socket.on('send_file', (obj) => {
    var fileStream = fs.createWriteStream('public/' + obj.session_link + '/' + obj.track_name + '.mp4', { flags: 'a' });
    fileStreams.push({ track_name: obj.track_name, file_stream: fileStream });
    fileStream.write(Buffer.from(new Uint8Array(obj.data)));
  })
  socket.on('send_canvas', (obj) => {
    socket.broadcast.emit('send_canvas', obj);
  })
  socket.on('send_invalidate_rect', (obj) => {
    socket.broadcast.emit('send_invalidate_rect', obj);
  })
  socket.on('send_screen_canvas', (obj) => {
    socket.broadcast.emit('send_screen_canvas', obj);
  })
  socket.on('get_all_room', (obj) => {
    socket.emit('get_all_room', { rooms: rooms });
  });
  socket.on('create_room', (obj) => {
    console.log('Trying to create room ' + obj.meeting_title + ': session_link ' + obj.session_link);

    var meeting_title = obj.meeting_title;
    var session_link = obj.session_link;
    client.video.rooms
      .create({
        type: 'group',
        uniqueName: meeting_title,
        recordParticipantsOnConnect: false
      })
      .then(room => {
        fs.mkdir('./public/' + session_link, (err) => {
          if (err) throw err;
        });

        var obj = {
          table: []
        };
        fs.writeFile("public/" + session_link + "/" + session_link + "_history.json", JSON.stringify(obj), 'utf8', function (err) {
          if (err) {
            console.log("An error occured while writing JSON Object to File.");
            return console.log(err);
          }

          console.log("JSON file has been saved.");
        });

        console.log(room);
        var _obj = {
          title: room.uniqueName,
          sid: room.sid,
          session_link: session_link,
          status: room.status,
          tracks: [],
          layout: 0,
          meeting_started: 0,
          recording_started: 0,
        }
        rooms.push(_obj);
        roomSid = room.sid;

        console.log('Room ' + room.uniqueName + ' created successfully');
        console.log('RoomSid=' + room.sid);
        console.log('Room ' + roomName + ' ready to receive client connections');
      })
      .catch(error => {
        console.log('Error creating room ' + error);
        socket.emit('room_error', {});
      });
  })
  socket.on('chat_text', (obj) => {
    socket.broadcast.emit('chat_text', obj);
  });
  socket.on('get_selected_track', (obj) => {
    console.log('get_selected_track');

    var _room = rooms.find(x => x.session_link == obj.session_link);
    if (_room) {
      console.log(_room.tracks);
      socket.emit('get_selected_track', { session_link: obj.session_link, tracks: _room.tracks });
    }

  })
  socket.on('set_layout', (obj) => {
    var _room = rooms.find(x => x.session_link == obj.session_link);
    if (_room) {
      _room.layout = obj.layout;
    }
    socket.broadcast.emit('set_layout', { session_link: obj.session_link, layout: obj.layout });
  })
  socket.on('get_layout', (obj) => {
    var _room = rooms.find(x => x.session_link == obj.session_link);
    if (_room) {
      socket.emit('get_layout', { session_link: obj.session_link, layout: _room.layout });
    }
  })
  socket.on('mute', (obj) => {
    socket.broadcast.emit('mute', obj);
  })
  socket.on('unmute', (obj) => {
    socket.broadcast.emit('unmute', obj);
  })
  socket.on('update_track_info', (obj) => {
    socket.broadcast.emit('update_track_info', obj);
  })

});

/*This function makes the cleanup upon program termination. This cleaup includes
completing the room if it's still active. Otherwise, the room will stay alive
for 5 minutes after all participants disconnect.*/
function prepareCleanTermination() {
  process.stdin.resume(); //so the program will not close instantly
  //do something when app is closing
  process.on('exit', exitHandler.bind(null, {
    cleanup: true
  }));
  //catches ctrl+c event
  process.on('SIGINT', exitHandler.bind(null, {
    exit: true
  }));
  //catches uncaught exceptions
  process.on('uncaughtException', exitHandler.bind(null, {
    exit: true
  }));

  function exitHandler(options, err) {
    if (roomSid) {
      client.video.rooms(roomSid)
        .update({
          status: 'completed'
        })
        .then(room => {
          console.log('Room ' + roomSid + ' completed');
          if (options.exit) process.exit();
        })
        .catch(error => {
          if (options.exit) process.exit();
        })
    }
  }
}