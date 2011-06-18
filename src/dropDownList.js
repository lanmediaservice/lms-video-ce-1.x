/****** dropDownList.js ******/
function dropDownList( editFieldDomId, overlap, optionsList) {
    var req = new Object();
    req.editFieldDomId = editFieldDomId;
    req.optionsList = optionsList;
    req.inited = 0;
    
    req.addLI = function( value ) {
        var option = document.createElement( 'OPTION' );
        req.list.appendChild( option );
        option.value = value;
        option.innerHTML = value;
    }
    
    req.clearList = function() {
        req.overlapper('visible');
        
        req.list.style.visibility = 'hidden';

        while ( req.list.childNodes.length > 0 ) {
            req.list.removeChild( req.list.firstChild );
        }

        if ( req.list == req.editField.parentNode.lastChild ) {
            req.editField.parentNode.removeChild( req.list );
        }

        req.selected = null;
    }

    req.fillList = function( jsList ) {
        req.clearList();
        
        req.list.style.visibility = 'visible';
        for ( var index = 0; index < jsList.length; index++ ) {
            req.addLI( jsList[index] );
        }
        
        if ( req.list.childNodes.length == 0 ) return;

        req.editField.parentNode.appendChild( req.list );

        req.overlapper('hidden');
        
        return;
    }
    
    req.overlapper = function(value){
        if (overlap && req.list.runtimeStyle) {
            for (var index = 0; index < overlap.length; index++) {
                document.getElementById(overlap[index]).style.visibility = value;
            }
        }
    }
    
    req.show = function(){
	    req.checkInit();
        req.list.style.visibility = 'visible';
        req.overlapper('visible');
    }   

    req.hide = function(){
	    req.checkInit();
        req.overlapper('hidden');
        req.list.style.visibility = 'hidden';
    }   

    req.checkInit = function(){
    	if (!req.editField) {
    		req.init();
    	}
	}
	
    req.init = function(){
	    req.editField = document.getElementById( req.editFieldDomId );
	    if ( !req.editField ) { throw "editField not found"; }
	
	    var span = document.createElement( 'span' );
	    span.style.position = 'relative';
	    span.style.display = 'block';
	    req.editField.setAttribute( 'autocomplete', "off" );
	    req.editField.parentNode.replaceChild( span, req.editField );
	    span.appendChild( req.editField );
	    
	    req.list = document.createElement( 'SELECT' );
	    req.list.className = 'dropDownList';
	    req.list.setAttribute( 'size', 20 );
		
		// IE fix
		if (req.list.runtimeStyle) { 
	        span.style.zoom = '1';
		    req.list.style.top = '1.7em';
	    }
	    req.fillList(req.optionsList); 
	   	req.list.value = req.editField.value;
		req.list.onchange = function() {
			if (req.editField.value!=req.list.options[req.list.selectedIndex].value) {
	        	req.editField.value = req.list.options[req.list.selectedIndex].value;
	        	req.editField.onchange();
        	}
        	req.hide();
        }
	    setTimeout(function(){req.editField.focus();},100);
    }   

    return req
}

