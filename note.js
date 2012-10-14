(function() { 

base64={PADCHAR:",",ALPHA:"ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_",getbyte:function(a,g){var b=a.charCodeAt(g);if(255<b)throw"INVALID_CHARACTER_ERR: DOM Exception 5";return b},encode:function(a){if(1!=arguments.length)throw"SyntaxError: Not enough arguments";var g=base64.PADCHAR,b=base64.ALPHA,f=base64.getbyte,d,c,e=[],a=""+a,h=a.length-a.length%3;if(0==a.length)return a;for(d=0;d<h;d+=3)c=f(a,d)<<16|f(a,d+1)<<8|f(a,d+2),e.push(b.charAt(c>>18)),e.push(b.charAt(c>>12&63)),e.push(b.charAt(c>>6&63)),e.push(b.charAt(c&63));switch(a.length-h){case 1:c=f(a,d)<<16;e.push(b.charAt(c>>18)+b.charAt(c>>12&63)+g+g);break;case 2:c=f(a,d)<<16|f(a,d+1)<<8,e.push(b.charAt(c>>18)+b.charAt(c>>12&63)+b.charAt(c>>6&63)+g)}return e.join("")}};

function getSelectedText(){var a=window.getSelection?window.getSelection():document.getSelection?document.getSelection():document.selection.createRange().text;if(!a||""==a)document.activeElement.selectionStart&&(a=document.activeElement.value.substring(document.activeElement.selectionStart.document.activeElement.selectionEnd));return a};

  var xmlhttp = new XMLHttpRequest(),
      key = window._note_bookmarklet_user_id || 0,
      selection = getSelectedText() || '';

  if (selection.length > 1000) {
    selection = selection.substring(997) + '...';
  }

  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState === 4) {
      var data = JSON.parse(xmlhttp.responseText),
          el = document.createElement("div"),
          b = document.getElementsByTagName("body")[0],
          txt = document.createTextNode(data.ok ? 'Noted.' : 'Error: ' + data.error);
      el.style.cssText='position:fixed;height:32px;width:100%;text-align:center;top:0;left:0;padding:10px;z-index=1001;font-size:32px;color:#222;background-color:#f99';
      el.appendChild(txt);
      b.appendChild(el);
      window.setTimeout(function () { txt = null, b.removeChild(el); }, data.ok ? 1000 : 3000);
      window._note_bookmarklet_user_id = null;
    }
  };
  xmlhttp.open('GET', '//note.grin.io/note/' + key + 
                      '?u=' + base64.encode(window.location.href) + 
                      '&t=' + base64.encode(window.document.title) + 
                      '&s=' + base64.encode(selection), 
               true); 
  xmlhttp.send();
})();
