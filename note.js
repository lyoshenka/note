(function() {

  bas64encode = function(a){a=""+a;if(0===a.length)return a;var z="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_",e=function(a,b){var c=a.charCodeAt(b);if(255<c)throw"INVALID_CHARACTER_ERR: DOM Exception 5";return c;},c,b,d=[],f=a.length-a.length%3;for(c=0;c<f;c+=3)b=e(a,c)<<16|e(a,c+1)<<8|e(a,c+2),d.push(z.charAt(b>>18)),d.push(z.charAt(b>>12&63)),d.push(z.charAt(b>>6&63)),d.push(z.charAt(b&63));
                switch(a.length-f){case 1:b=e(a,c)<<16;d.push(z.charAt(b>>18)+z.charAt(b>>12&63));break;case 2:b=e(a,c)<<16|e(a,c+1)<<8,d.push(z.charAt(b>>18)+z.charAt(b>>12&63)+z.charAt(b>>6&63));}return d.join("");};

  function getSelectedText(){
    return window.getSelection ? window.getSelection().toString() :
           (document.getSelection ? document.getSelection().toString() :
           (document.selection ? document.selection.createRange().text :
           null));
  }

  var encode = function(str) { return bas64encode(unescape(encodeURIComponent(str))); },
      xmlhttp = new XMLHttpRequest(),
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
      el.style.cssText='position:fixed;height:32px;width:100%;text-align:center;top:0;left:0;padding:15px;z-index=999999;font-size:32px;color:#222;background-color:#f99';
      el.appendChild(txt);
      b.appendChild(el);
      window.setTimeout(function () { txt = null, b.removeChild(el); }, data.ok ? 1000 : 3000);
      window._note_bookmarklet_user_id = null;
    }
  };
  xmlhttp.open('GET', '//note.grin.io/note/' + key +
                      '?u=' + encode(window.location.href) +
                      '&t=' + encode(window.document.title) +
                      '&s=' + encode(selection),
               true);
  xmlhttp.send();
})();
