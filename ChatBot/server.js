'use strict';
var fs = require('fs');
var express = require('express');
var http = require('http');
var app = express();
var serverPort = 3000;
var bodyParser = require('body-parser');
var path = require('path');
app.use(express.static('public'))
app.use(express.static('files'))
app.use('/static', express.static(path.join(__dirname, 'public')))
app.use(function (req, res, next) {
  res.header("Access-Control-Allow-Origin", "*"); // update to match the domain you will make the request from
  res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
  if (req.method === 'OPTIONS') {
    res.header('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH');
    return res.status(200).json({});
  };
  next();
});
app.use(bodyParser.json());
var server = http.createServer(app);
app.get('/', function (req, res) {
  res.sendFile(__dirname + '/public/index.html');
});
function generate_js(tabs) {
  for (let idx = 0; idx < tabs.length; idx++) {
    console.log(tabs[idx]);
    gen(tabs[idx]);
  }
}
function gen(tab) {
  content += "var goto_" + tab.index + " = function(){\n";
  for (let q_idx = 0; q_idx < tab.question_nodes.length; q_idx++) {
    if (tab.question_nodes[q_idx].type == 0) {
      var str = tab.question_nodes[q_idx].content;
      str = str.replace(/'/g, "\\'");
      str = str.replace(/\n/g, "");
      if (q_idx == 0) {
        content += "botui.message\n.bot({\ndelay:500,\ncontent:'" + str + "'\n})\n";
      } else {
        content += "botui.message\n.bot({\ndelay:500,\ncontent:'" + str + "'\n});\n";
      }
    } else {
      content += "return botui.action.text({\n";
      content += "delay: 1000,\n";
      content += "action: {\n";
      content += "size: 30,\n";
      content += "icon: 'email',\n";
      content += "value: '',\n";
      content += "placeholder: ''\n";
      content += "}\n";
      content += "});\n";
    }


    if (q_idx == 0) {
      content += ".then(function(){\n";
    } else {
      content += "}).then(function(){\n";
    }
  }


  if (tab.reply_nodes.length > 0) {
    content += "return botui.action.button({\n";
    content += "delay:1000,\n";
    content += "addMessage:false,\n";
    content += "action:[";
  }

  for (let r_idx = 0; r_idx < tab.reply_nodes.length; r_idx++) {
    var str = tab.reply_nodes[r_idx].content;
    str = str.replace(/'/g, "\\'");
    str = str.replace(/\n/g, "");

    content += "{\n";
    content += "text : '" + str + "',\n";
    content += "value : '" + tab.reply_nodes[r_idx].go_to + "'"
    content += "},";

  }
  if (tab.reply_nodes.length > 0) {
    content += "]\n";
    content += "});}).then(function(res){\n";
    for (let r_idx = 0; r_idx < tab.reply_nodes.length; r_idx++) {
      if (r_idx == 0) {
        content += "if(res.value == '" + tab.reply_nodes[r_idx].go_to + "') {";
      } else {
        content += " else if(res.value == '" + tab.reply_nodes[r_idx].go_to + "') {";
      }
      content += "botui.message.human({\n";
      content += "delay : 500,\n";
      content += "content: res.text\n";
      content += "});\n";
      content += "goto_" + tab.reply_nodes[r_idx].go_to + "();\n";

      content += "}";

    }
  }


  if (tab.question_nodes.length > 0) {
    content += "});";
    content += "\n";
  }

  content += "}";
  content += "\n";
}
var content = '';
app.post('/create-code', function (req, res) {
  content = "var botui = new BotUI('delivery-bot');\n";
  let tabs = req.body;
  generate_js(tabs);
  content += "goto_1();\n"
  fs.writeFileSync('public/emulate/result.js', content, "UTF-8", { 'flags': 'w' });
  res.json({ status: 200 });
});
server.listen(serverPort, function () {
  console.log('server up and running at %s port', serverPort);
});