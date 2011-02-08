// js utilities
//
// Version: 20110126
// License: GPL

var Utils = {
  version: '0.0.20110126'
};

if (typeof Array.prototype.push == 'undefined') { // {{{
  Array.prototype.push = function() {
    for (var i=0; i<arguments.length; i++) {
      this[this.length] = arguments[i];
    }
  }
} // }}}

if (typeof Array.prototype.pop == 'undefined') { // {{{
  Array.prototype.pop = function() {
    var undef = {};
    if (this.length == 0) return undef.ined; // return undefined
    var value = this[this.length - 1];
    delete this[this.length - 1];
    return value;
  }
} // }}}

Utils.browser = new (function() { // {{{
  var ua = navigator.userAgent.toLowerCase();
  this.IE = !!(window.attachEvent && !window.opera);
  this.Opera = !!window.opera;
  this.IEVersion = parseFloat(ua.substr(ua.indexOf('msie') + 4));
})(); // }}}


{(function() {
  var escape = function(str) { // {{{
    var esc = [
      ['\\\\','\\'], ['"','"'], ['\b','b'], 
      ['\f','f'], ['\n','n'], ['\r','r'], ['\t','t']
    ];
    str = str || '';
    for (var i = 0; i < esc.length; ++i) {
      str = str.replace(new RegExp(esc[i][0], 'g'), '\\' + esc[i][1]);
    }
    return str;
  } // }}}
  function isString(obj) {
    return (typeof obj == 'string' || obj instanceof String);
  }
  
var helperObj = function() {
  var visited = [];
  function isVisited(obj) {
    for (var i = 0; i < visited.length; ++i) {
      if (obj == visited[i]) return true;
    }
    return false;
  }
  function contents(obj, formattingFunction) {
    var props = [];
    for (var prop in obj) {
      try {
        var value = obj[prop];
        if (typeof value == 'function' || typeof value == 'undefined') {
          continue;
        }
        if (typeof value == 'object' && !isString(value) && isVisited(value)) {
          // object recursion
          continue;
        }
        props[props.length] = formattingFunction(obj, prop);
      } catch (ex) {}
    }
    return props.join(',');
  }

  function toJSON(obj) { // {{{
    function arrayItem(obj, x) {
      return toJSON(obj[x]);
    }
    function objectItem(obj, x) {
      return '"' + escape(x) + '":' + toJSON(obj[x]);    
    }

    if (obj == null) return "null";
    if (isString(obj)) {
      return '"' + escape(obj) + '"';
    }
    if (obj instanceof Date) {
      return '"' + obj.toUTCString() + '"';
    }
    if (obj instanceof RegExp) {
      return '"' + escape(obj.toString()) + '"';
    }
    if (obj instanceof Boolean) {
      return obj.toString();
    }
    if (typeof obj == 'object') {
      visited[visited.length] = obj;
      if (obj instanceof Array) {
        return '[' + contents(obj, arrayItem) + ']';
      } else {
        return '{' + contents(obj, objectItem) + '}';
      }
    }
    if (typeof obj == 'function') return;
    return '' + obj; // numbers and booleans
  } // }}}
  this.doit = toJSON;
}

  Utils.toJSON = function(obj) {
    return (new helperObj).doit(obj);
  }
})()}

// creates object with simple access to ascending and descending sorting
Utils.Sortable = function(arr) {
  var lt = function(a, b) { return a < b },
      gt = function(a, b) { return a > b },
      sortfunc = function(comparator, prop) {
        if (typeof prop == 'undefined') {
          return function(a, b) {
            if (a == b) return 0;
            if (comparator(a, b)) return -1;
            return 1;
          }
        } else {
          return function(a, b) {
            if (a[prop] == b[prop]) return 0;
            if (comparator(a[prop], b[prop])) return -1;
            return 1;
          }
        }
      };
  return new (function(data) {
    this.sortAscending = function(prop) {
      data.sort(sortfunc(lt, prop));
      return this;
    }
    this.sortDescending = function(prop) {
      data.sort(sortfunc(gt, prop));
      return this;
    }
  })(arr);
}

Utils.JSONable = function(obj) { // {{{
  obj.toString = function() { 
    return Utils.toJSON(this);
  }
  return obj;
} // }}}

Utils.windowSize = function() { // {{{
  var myWidth = 0, myHeight = 0;
  if (typeof window.innerWidth == 'number') {
    // Non-IE
    myWidth = window.innerWidth;
    myHeight = window.innerHeight;
  } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) {
    // IE 6+ in standards compliant mode
    myWidth = document.documentElement.clientWidth;
    myHeight = document.documentElement.clientHeight;
  } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) {
    // IE 4 compatible
    myWidth = document.body.clientWidth;
    myHeight = document.body.clientHeight;
  }
  // var haveScrollX = (document.documentElement ? document.documentElement.scrollWidth : document.body.scrollWidth) > myWidth;
  // var haveScrollY = Utils.browser.IE ? true : ((document.documentElement ? document.documentElement.scrollHeight : document.body.scrollHeight) > myHeight);

  return Utils.JSONable({width: myWidth, height: myHeight});
} // }}}

Utils.getScroll = function() { // {{{
  var scrOfX = 0, scrOfY = 0;
  if (typeof window.pageYOffset == 'number') {
    // Netscape compliant
    scrOfY = window.pageYOffset;
    scrOfX = window.pageXOffset;
  }
  else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
    // DOM compliant
    scrOfY = document.body.scrollTop;
    scrOfX = document.body.scrollLeft;
  } 
  else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
    // IE6 standards compliant mode
    scrOfY = document.documentElement.scrollTop;
    scrOfX = document.documentElement.scrollLeft;
  }
  return Utils.JSONable({x: scrOfX, y: scrOfY});
} // }}}

Utils.stopEventPropagation = function(event) { // {{{
  event = event || window.event;
  if (!event) return;
  event.cancelBubble = true;
  if (event.preventDefault) {
    event.preventDefault();
  }
} // }}}

Utils.mousePosition = function(event) { // {{{
  var tempX = 0
  var tempY = 0
  event = event || window.event;
  if (Utils.browser.IE) { // IE
    var scroll = Utils.getScroll();
    tempX = event.clientX + scroll.x
    tempY = event.clientY + scroll.y
  } 
  else {  // NS
    tempX = event.pageX
    tempY = event.pageY
  }  
  // catch possible negative values
  if (tempX < 0) { tempX = 0 }
  if (tempY < 0) { tempY = 0 }
  return Utils.JSONable({x:tempX, y:tempY, left:tempX, top:tempY});
} // }}}

Utils.elementPosition = function(element) { // {{{
  var left = 0, top = 0;
  if (element.offsetParent) {
    do {
      left += element.offsetLeft;
      top += element.offsetTop;
    } while (element = element.offsetParent);
  }
  return Utils.JSONable({left:left, top:top});
} // }}}

Utils.isString = function(value) { // {{{
  return (typeof value == 'string') || (value instanceof String);
} // }}}

Utils.trim = function(str) { // {{{
  str = str || '';
  return ('' + str).replace(/^\s+/g, '').replace(/\s+$/g, '');
} // }}}

Utils.setAttributes = function(element, attrs) { // {{{
  if (attrs) {
    for (var attr in attrs) {
      if (attr == 'style') {
        if (Utils.isString(attrs.style)) {
          element.setAttribute(attr, attrs.style);
        } 
        else {
          for (var prop in attrs.style) {
            if (prop == 'float') {
              element.style.styleFloat = attrs.style[prop]; // IE
              element.style.cssFloat = attrs.style[prop];
            }
            else {
              element.style[prop] = attrs.style[prop];
            }
          }
        }
      }
      // Experimental update [[[
      else if (attr == 'tag' || attr == 'tagName') {
        // tag attribute is read-only, and is used for childNodes
      }
      else if (attr == 'childNodes') {
        var childNodes = attrs.childNodes;
        if (childNodes instanceof Array) {
          for (var i = 0; i < childNodes.length; ++i) {
            var spec = childNodes[i];
            if (typeof spec == 'undefined') {
              // that's for IE (at least up to version 8), since: 
              // [a,b,c].length == 3
              // [a,b,c,].length == 4 (the last element is undefined)
              continue;
            }
            var tag = spec.tag || spec.tagName;
            if (tag) {
              var child = Utils.createElement(tag, spec);
              if (child) element.appendChild(child);
            }
          }
        }
      } // ]]]
      else if (Utils.isString(attrs[attr])) {
        switch (attr) {          
          case 'class':
          case 'className':
            element.className = attrs[attr];
            break;
          case 'innerHTML':
            element.innerHTML = attrs[attr];
            break;
          case 'for':
          case 'htmlFor':
            element.htmlFor = attrs[attr];
            break;
          case 'value':
            if (element.tagName == 'TEXTAREA') { 
              // value in textarea must be set using property rather than 
              // setAttrubute, otherwise it is ignored in Gecko browsers
              element.value = attrs[attr];
              break;
            }
          default:
            element.setAttribute(attr, attrs[attr]);
            break;
        }
      }
      else if (typeof attrs[attr] != 'undefined') { // IE requires this check
        element[attr] = attrs[attr];
      }
    }
  }
} // }}}

Utils.createIframe = function(parent, attrs) { // {{{
  var doc = parent.ownerDocument ? parent.ownerDocument : parent.document;
  var iframe = null;
  var name = attrs.name || attrs.id;

  if (Utils.browser.IE && Utils.browser.IEVersion < 5.5) {
    // embedding an IFRAME in IE 5.0 is a bit tricky
    // and sometimes it just doesn't work
    parent.innerHTML += '<iframe src="" name="' + name + '" id="' + attrs.id + '"></iframe>';
    try {
      window.frames[name].location.replace(attrs.src);
      iframe = doc.getElementById(attrs.id);
      Utils.setAttributes(iframe, attrs);
    } catch (e) {}
  } else {
    iframe = Utils.createElement('iframe', attrs, doc);
    parent.appendChild(iframe);
  }
  return iframe;
} // }}}

Utils.createElement = function(tag, attrs, doc) { // {{{
  doc = doc || document;
  tag = tag.toUpperCase();
  var element = document.createElement(tag);
  Utils.setAttributes(element, attrs);  
  return element;
} // }}}

Utils.submit = function(form) { // {{{
  if (document.createEvent) { // DOM
    var event = document.createEvent('HTMLEvents');
    event.initEvent('submit', false, true);
    form.dispatchEvent(event);
  }
  else if (document.createEventObject) { // IE
    if (form.fireEvent('onsubmit')) {
      form.submit();
    }
  }
  else { // Compatibility mode
    if ((typeof form.onsubmit != 'function') || form.onsubmit()) { 
      form.submit();
    }
  }
} // }}}

Utils.importNode = function(node, deep, doc) { // {{{
  if (!Utils.imported) {
    // mapping between old and new ids for imported nodes
    Utils.imported = {idMap:{}, labels:[]};
  }
  doc = doc || document;
  switch (node.nodeType) {
    case 1: // ELEMENT_NODE
      var tagName = node.nodeName.toLowerCase();
      var attrs = {};
      if (node.attributes) {
        for (var i=0; i<node.attributes.length; i++) {
          var attr = node.attributes[i].nodeName;
          attrs[attr] = node.getAttribute(attr);
        }
        if (attrs.id && doc.getElementById(attrs.id)) {
          // avoid id duplicates
          var newId = attrs.id;
          do { 
            newId += '-imported';
          } while (doc.getElementById(newId));
          Utils.imported.idMap[attrs.id] = newId;
          attrs.id = newId;
          // update imported labels referencing this element
          for (var i=0; i<Utils.imported.labels.length; i++) {
            var label = Utils.imported.labels[i];
            if (Utils.imported.idMap[label.htmlFor]) {
              label.htmlFor = Utils.imported.idMap[label.htmlFor]
            }
          }
        }
      }
      try {
        var element = Utils.createElement(tagName, attrs, doc);
      } catch (e) {
        return null;
      }
      if (deep && node.childNodes) {
        if (tagName == 'table') { 
          // add a tbody element when needed
          var tbody = null;
          for (var i=0; i<node.childNodes.length; i++) {
            var child = Utils.importNode(node.childNodes[i], true, doc);
            if (child) {
              var childTag = child.tagName ? child.tagName.toLowerCase() : null;
              if (childTag == 'tr') {
                if (!tbody) {
                  tbody = Utils.createElement('tbody');
                  element.appendChild(tbody);
                }
                tbody.appendChild(child);                  
              } else {
                element.appendChild(child);
              }
            }
          }
        } else if (tagName == 'style' || tagName == 'script') {
          // IE throws 'Unexpected call to method or property access' exception
          // when appending child nodes to treat this kind of elements. 
          // Lets just say that both STYLE and SCRIPT elements cannot be imported.
        }
        else {
          for (var i=0; i<node.childNodes.length; i++) {
            var child = Utils.importNode(node.childNodes[i], true, doc);            
            if (child) element.appendChild(child);
          }
        }        
      }
      if (tagName == 'label') {
        Utils.imported.labels.push(element);
      }
      return element;
    case 3: // TEXT_NODE
    case 4: // CDATA_SECTION_NODE
      return doc.createTextNode(node.nodeValue);
  }
  return null;
} // }}}

Utils.removeChildNodes = function(element) { // {{{
  if (!element) return;
  while (element.hasChildNodes()) {
    element.removeChild(element.lastChild);
  }
} // }}}

Utils.cancelSelection = function() { // {{{
  if (Utils.browser.IE) {
    with (document.selection.createRange()) {
      expand("word");
      execCommand("unselect");
    }
  }
  else {
    try {
      window.getSelection().collapseToStart();
    } catch (e) {}
  }
} // }}}

Utils.setOpacity = function(element, opacity) { // {{{
  element.style.opacity = opacity;
  element.style.MozOpacity = opacity;
  element.style.KhtmlOpacity = opacity;
  element.style.filter = 'alpha(opacity=' + (100 * opacity) + ')';
} // }}}

Utils.parseInt = function(str) { // {{{
  var value = parseInt(str);
  return isNaN(value) ? 0 : value;
} // }}}

Utils.getStyle = function(element, styleProp) { // {{{
  var style = '';
  if (element.currentStyle) { // IE
    if (styleProp == 'float') styleProp = 'styleFloat';
    // change styleProp to camel-case
    styleProp = styleProp.replace(/(-[a-z])/g, function(x) { return x.replace(/-/,'').toUpperCase(); });
    style = element.currentStyle[styleProp];
  }
  else if (window.getComputedStyle)
    style = document.defaultView.getComputedStyle(element, null).getPropertyValue(styleProp);
  return style;
} // }}}

Utils.GUID = function() { // {{{
  var guid = '';
  for (var j = 0; j < 32; j++) {
    if (j == 8 || j == 12 || j == 16 || j == 20) {
      guid = guid + '-';
    }
    guid = guid + Math.floor(Math.random() * 16).toString(16).toUpperCase();
  }
  return guid;
} // }}}

Utils.setCookie = function(name, value, attrs) { // {{{
  var cookie  = name + "=" + escape(value);
  attrs = attrs || {};
  if (attrs.expires) {
    // expires - time in seconds
    var exptime = new Date();
    exptime.setSeconds(exptime.getSeconds() + attrs.expires);
    cookie += "; expires=" + exptime.toGMTString();
  }
  if (attrs.path)   cookie += "; path=" + path;
  if (attrs.domain) cookie += "; domain=" + domain;
  if (attrs.secure) cookie += "; secure";
  document.cookie = cookie;
} // }}}

Utils.getCookie = function(name) { // {{{
  var dc = document.cookie;
  var nameEq = name + "=";
  var start = dc.indexOf("; " + nameEq);
  if (start == -1) {
    start = dc.indexOf(nameEq);
    if (start != 0) return null;
  }
  else start += 2;
  var end = dc.indexOf(";", start);
  if (end == -1) end = dc.length;
  return unescape(dc.substring(start + nameEq.length, end));
} // }}}

Utils.cookie = function(name, value, attrs) { // {{{
  if (arguments.length < 2) {
    return Utils.getCookie(name);
  }
  return Utils.setCookie(name, value, attrs);
} // }}}

Utils.forEach = function(array, action) { // {{{
  for (var i = 0; i < array.length; ++i) {
    if (typeof array[i] == 'undefined') continue;
    action.call(array[i], i);
  }
} // }}}

Utils.toggle = function(targetElement, handlers) { // {{{
  if (Utils.isString(targetElement)) {
    targetElement = document.getElementById(targetElement);
  }
  if (targetElement.offsetHeight == 0) {
    if (handlers && typeof handlers.onShow == 'function') handlers.onShow();
    targetElement.style.display = 'block';
  } else {
    if (handlers && typeof handlers.onHide == 'function') handlers.onHide();
    targetElement.style.display = 'none';
  }
} // }}}

function $E(id) { // {{{
  return Utils.isString(id) ? document.getElementById(id) : id;
} // }}}

Utils.onloadListeners = [];

Utils.addOnloadListener = function(listener) { // {{{
  if (typeof listener != 'function') return;
  Utils.onloadListeners.push(listener);
} // }}}

function $L(listener) { // {{{
  Utils.addOnloadListener(listener); 
} // }}}

Utils.onload = function() { // {{{
  var listener;
  while (listener = Utils.onloadListeners.pop()) {
    listener();
  }
} // }}}

{(function() { // attach onload handler {{{
  var prevOnload = window.onload;
  window.onload = function() {
    Utils.onload();
    if (typeof prevOnload == 'function') prevOnload();
  }
})();} // }}}

Utils.Date = {};

Utils.Date.parse = function(str) { // {{{
  var date = new Date();
  date.setTime(Date.parse(str));
  return date;
} // }}}

Utils.Date.leadingZero = function(value, length) { // {{{
  length = length || 2;
  value = '' + value;
  while (value.length < length) {
    value = '0' + value;
  }
  return value;
} // }}}

Utils.Date.format = function(format, date) { // {{{
  date = date || new Date();
  var wildcards = {
    Y: function() { return Utils.Date.leadingZero(date.getFullYear(), 4); },
    m: function() { return Utils.Date.leadingZero(date.getMonth() + 1); },
    d: function() { return Utils.Date.leadingZero(date.getDate()); },
    H: function() { return Utils.Date.leadingZero(date.getHours()); },
    i: function() { return Utils.Date.leadingZero(date.getMinutes()); },
    s: function() { return Utils.Date.leadingZero(date.getSeconds()); }
  };
  var out = '';
  for (var i=0; i<format.length; i++) {
    var c = format.charAt(i);
    out += wildcards[c] ? wildcards[c]() : c;
  }
  return out;
} // }}}

Utils.Date.now = function() { // {{{
  return Utils.Date.format("Y-m-d H:i:s");
} // }}}

Utils.Xml = {
  parse: function(txt) {
    var xmlDoc = null;
    try {
      if (window.DOMParser) {
        var parser = new DOMParser();
        xmlDoc = parser.parseFromString(txt, "text/xml");
      } else { // IE
        xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
        xmlDoc.async = "false";
        xmlDoc.loadXML(txt);
      }
    } catch (e) {}
    return xmlDoc;
  }
}

// vim: et sw=2 fdm=marker
