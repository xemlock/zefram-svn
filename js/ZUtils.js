var Zefram = {};

Zefram.Form = {};



//if (typeof advAJAX != 'undefined') {
  // load default AJAX drivers 
//  Zefram.ajax.get = advAJAX.get;
//  Zefram.ajax.post = advAJAX.post;
//  Zefram.ajax.submit = advAJAX.submit;
//} else if (typeof jQuery != 'undefined') {
  


  Zefram.Form.submit = function(form, params) {
    params.data = jQuery(form).serialize();

    if (params.showSpinner) params.showSpinner(form);

    // don't disable fields before calling serialize (unless you wan't to
    // get an empty string)
    for (var i = 0; i < form.elements.length; ++i) {
      form.elements[i].disabled = true;
    }

    if (form.method) params.type = form.method;
    if (!params.type) params.type = 'POST';
    params.dataType = 'json';
    jQuery.ajax(params);
  }


Zefram.Form.newResponseHandler = function(element, params, form) { // {{{
  return function (data, textStatus, xhr) {
    var success = true;
    if (data.code == '200') {
      if (data.xml) {
        var doc = Utils.Xml.parse(data.xml), response = null;
        if (doc) {
          response = doc.getElementsByTagName('xml')[0];
        }
        if (!response) { // critical error: improperly formatted data from server
          if (params.error) params.error(xhr);
          alert('Critical error: ' + xhr.responseText);
          success = false;
        }
      }
      if (form && form.elements) {
        // release blocked fields
        for (var i = 0; i < form.elements.length; ++i) {
          if (form.elements[i].disabled) {
            form.elements[i].disabled = false;
          }
        }
      }
      if (params.hideSpinner) params.hideSpinner();
      if (success && params.success) params.success(xhr, form);
      
    } else {
      // error: invalid data provided - import new form
      Zefram.Form.importHTMLFromResponse(data, element, params);
      if (params.hideSpinner) params.hideSpinner();
    }

    if (typeof params.finalize == 'function') params.finalize();
  }
} // }}}

// onsubmit - attachSubmit
Zefram.Form.attachSubmit = function(form, params) { // {{{

  params = params || {};
  if (!params.action) {
    if (form.action == '') {
      params.action = params.url || ('' + document.URL);
    }
    else {
      params.action = form.action;
    }
  }
  if (!params.wrapper) { 
    // formularz musi byc owiniety w div - do tego divu bedzie ladowana tresc AJAXowa
    var wrapper = Utils.createElement('div', {'class':'form-wrapper'});
    form.parentNode.insertBefore(wrapper, form);
    wrapper.appendChild(form.parentNode.removeChild(form));
    params.wrapper = wrapper;
  }
  var _onsubmit = typeof params.onsubmit == 'function' ? params.onsubmit : function() { return true };
  form.onsubmit = function() {
    if (!_onsubmit.call(form)) {
      return false;
    }

    // release locked form fields when error occurs
    params.error = function(xhr) {
      for (var i = 0; i < form.elements.length; ++i) {
        if (form.elements[i].disabled) {
          form.elements[i].disabled = false;
        }
      }
      if (params.hideSpinner) {
        params.hideSpinner();
      }
      alert('Submit error: ' + xhr.responseText);
    }

    // show overlay
    Zefram.Form.submit(this, {
      url: params.action, 
      success: Zefram.Form.newResponseHandler(params.wrapper, params, form),
      error: params.error,
      showSpinner: params.showSpinner,
      hideSpinner: params.hideSpinner      
    });
    return false;
  }
} // }}}

Zefram.Form.importHTMLFromResponse = function(ajaxData, element, params) { // {{{
  if (!ajaxData.xml) return;

  params = params || {};
  function replaceSubmit(elem) {
    if (('' + elem.tagName).toLowerCase() == 'form') {
      Zefram.Form.attachSubmit(elem, params);
      return;
    }
    for (var i = 0; i < elem.childNodes.length; i++) {
      replaceSubmit(elem.childNodes[i]);
    }
  }
  Utils.removeChildNodes(element);
  
  var doc = Utils.Xml.parse(ajaxData.xml), ajax = null;
  if (doc) {
    ajax = doc.getElementsByTagName('xml')[0];
  }
  if (!ajax) {
    if (params.error) params.error(xhr);
    alert('Critical error: ' + xhr.responseText);
    return;
  }

  for (var i=0; i<ajax.childNodes.length; i++) {
    var node = Utils.importNode(ajax.childNodes[i], true);
    if (node) element.appendChild(node);
  }
  if (!params.noAjaxSubmit) {
    replaceSubmit(element);
  }
  if (typeof params.onload == 'function') params.onload(ajaxData, element);
} // }}}

/**
 * url - URL to fetch elements from
 * element - where to import response elements (element's child nodes will be deleted before import)
 * params - additional parameters:
 *  .success - funkcja odpalana po pomyslnym przeslaniu formularza i otrzymaniu pozytywnej odpowiedzi
 *  .onload - funkcja uruchamiana po wykonaniu importu z otrzymanego XMLa
 *  .onerror - funkcja odpalana podczas wystapienia bledu krytycznego
 *  .noAjaxSubmit - ustawienie gdy wczytane formularze nie maja byc przesylane AJAXem
*/

Zefram.Form.fetch = function(url, element, params) { // {{{
  params = params || {};
  params.action = url; // .action nie .url bo Chrome dla pustego action sam ustawia wartosc!
  // Firefox: <form> -> form.action == form.getAttribute('action') == ''
  // Chrome: <form> -> form.action == docuement.URL, form.getAttribute('action') == ''
  jQuery.ajax({
    type: 'get',
    dataType: 'json',
    url: url,
    success: function(data) {
      Zefram.Form.importHTMLFromResponse(data, element, params);
    }
  });
} // }}}


// vim: et sw=2 enc=utf-8 fdm=marker
