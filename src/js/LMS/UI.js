/**
 * @copyright 2006-2011 LanMediaService, Ltd.
 * @license    http://www.lanmediaservice.com/license/1_0.txt
 * @author Ilya Spesivtsev <macondos@gmail.com>
 * @version $Id: UI.js 700 2011-06-10 08:40:53Z macondos $
 */
 
JSAN.require('LMS.Signalable'); 
 
LMS.UI = Class.create(LMS.Signalable, {
    lastMessageId: 0, 
    showUserError: function (code, message, level)
    {
        if (!level) {
            level = 'warn';
        }
        var text = 'Error #' + code + (message? ': ' + message: '');
        
        this.showMessage(text, level);
    },
    
    showUserMessage: function (message)
    {
        this.showMessage(message, 'info');
    },
    
    showMessage: function (message, level)
    {
        var messageElement = new Element('DIV');
        messageElement.addClassName(level);
        messageElement.innerHTML = message.escapeHTML();
        
        $('user_message').appendChild(messageElement);
        $('user_message').show();
        
        setTimeout(function(){
            messageElement.fade();
        }, 15000);
        
        setTimeout(function(){
            messageElement.remove();
        }, 20000);
        
    }, 
    highlightElement: function (domId)
    {
        new Effect.Highlight(domId, {startcolor: '#ffebe8'});
    },    
    reload: function()
    {
        window.location.reload(true);
        window.location.href = unescape(window.location.pathname);
    },
    
    isEnterKey: function(e)
    {
        if (!e) e = window.event;
        var characterCode;
        if(e.which) {
            characterCode = e.which;
        }  else {
            characterCode = e.keyCode;
        }

        return (characterCode == 13);
    }
});

