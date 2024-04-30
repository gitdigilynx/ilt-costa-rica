<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Wicrew\DateAvailability\Entity\DateAvailability;

class DateAvailabilityCalender extends AbstractController
{
    /**
     * @Route("admin/date/availability/calender", name="date_availability_calender")
     */
    public function index(): JsonResponse
    {
       ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.3/moment.min.js"></script>

  <script>
    let selected_dates = new Array();
    document.addEventListener('DOMContentLoaded', function () {
      var calendarEl = document.getElementById('calendar');

      var calendar = new FullCalendar.Calendar(calendarEl, {
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        initialDate: moment().format(),
        navLinks: true, // can click day/week names to navigate views
        selectable: true,
        selectMirror: true,
        select: function (arg) {
          selected_dates['start'] = moment(arg.start).format();
          selected_dates['end'] = moment(arg.end).subtract(1, 'd').format();
        },
        eventClick: function (arg) {
          if (confirm('Are you sure you want to make it available?')) {
            arg.event.remove()
          }
        },
       
        editable: true,
        dayMaxEvents: true, // allow "more" link when too many events

      });
      calendar.render();
    });
  </script>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noodp, noimageindex, notranslate, nocache"/>
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
	<meta name="generator" content="EasyAdmin"/>

	<title>
		Date Availability</title>

	<link rel="stylesheet" href="/bundles/easyadmin/app.css">

	<link rel="stylesheet" href="/bundles/wicrewcore/stylesheet/glyphicon.min.css">
	<link rel="stylesheet" href="/bundles/wicrewcore/stylesheet/custom-admin.min.css">
	<link rel="stylesheet" href="/bundles/wicrewcore/stylesheet/jquery-ui.css">
	<link rel="stylesheet" href="/bundles/wicrewcore/stylesheet/style.css">
	<link rel="stylesheet" href="/bundles/wicrewcore/javascript/jquery.fancybox.min.css">
	<link rel="stylesheet" href="/bundles/wicrewcore/stylesheet/jquery.timepicker.min.css">


	<link rel="icon" type="image/x-icon" href="/favicon.ico"/>

	<script src="/bundles/easyadmin/app.js"></script>

	<script type="text/javascript">
		var validateErrorMsg = {
      error: 'Error',
      required: 'Please do not leave this field empty.',
      filetype: 'File upload is wrong type',
      filesize: 'File upload is size than',
      filewidth: 'File upload have width less than',
      fileheight: 'File upload have height less than',
      minlength: 'Please put value length than',
      maxlength: 'Please put value length less than',
      isnumber: 'Please put value in number only',
      isfloat: 'Please put value in decimal only',
      morethanzero: 'Please put value in number more than zero'
    };
	</script>

</head>

<body id="easyadmin-list-DateAvailability" class="easyadmin list list-dateavailability">
	<script>
		document.body.classList.add('easyadmin-content-width-' + (
      localStorage.getItem('easyadmin/content/width') || 'normal'
      ), 'easyadmin-sidebar-width-' + (
      localStorage.getItem('easyadmin/sidebar/width') || 'normal'
    ));
	</script>

	<div class="wrapper">
		<header class="main-header">
			<nav class="navbar" role="navigation">
				<button id="navigation-toggler" type="button" aria-label="Toggle navigation">
					<img src="../images/sidebar.svg"/>
				</button>

				<div id="header-logo">
					<a class="logo logo-long" title="ILT Costa rica (1961)" href="/admin/">
						ILT Costa rica (1961)
					</a>
				</div>
			</nav>


			<div class="content-top navbar-custom-menu">
				<div class="user user-menu-wrapper " data-toggle="popover" data-placement="bottom" data-container=".user-menu-wrapper" data-content="&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;div&#x20;class&#x3D;&quot;popover-content-section&#x20;user-details&#x20;user-has-actions&quot;&gt;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;p&#x20;class&#x3D;&quot;small&#x20;text-muted&#x20;mb-0&quot;&gt;Logged&#x20;in&#x20;as&lt;&#x2F;p&gt;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;p&#x20;class&#x3D;&quot;user-details-name&quot;&gt;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;admin&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;&#x2F;p&gt;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;&#x2F;div&gt;&#x0A;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;div&#x20;class&#x3D;&quot;popover-content-section&#x20;user-menu&quot;&gt;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;a&#x20;class&#x3D;&quot;user-action&#x20;user-action-logout&quot;&#x20;href&#x3D;&quot;&#x2F;logout&quot;&gt;Sign&#x20;out&lt;&#x2F;a&gt;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&lt;&#x2F;div&gt;&#x0A;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;&#x20;" data-html="true">
					<i class="fa fa-fw fa-user-circle user-avatar"></i>
					<span class="user-name">admin</span>
				</div>
			</div>
		</header>

		<aside class="main-sidebar">
			<section class="sidebar">


				<ul class="sidebar-menu" data-widget="tree" data-animation-speed="250">


					<li class="   ">


						<a href="/admin/?entity=Dashboard&amp;action=list" class="">
							<i class="fa fa-fw fa-car"></i>
							<span>Dashboard</span>
						</a>


					</li>
					
				</ul>


			</section>

		</aside>

		<div class="content-wrapper">
			<div id="ajax_loader">
				<div class="spinner-border" role="status">
					<span class="sr-only">Loading...</span>
				</div>
			</div>

			<div class="button_container">
				<button id="unavailable_btn"> Make dates un-available </button>
				<button id="available_btn"> Make dates available </button>
			</div>

			<div id='calendar'></div>

      

      <script>
  
        let month_div_interval = setInterval(function () {
  
          let month_div        = $('h2#fc-dom-1');
          if( month_div.length > 0 ){
            month_div.on('DOMSubtreeModified', function(){

              $.ajax({
                url:'/get/date/availability',
                type: "GET",
                dataType: "json",
                data: {
                  "current_date": moment("14" + month_div.text() ).format(),
                },
                async: true,
                success: function (data) {
                  $("div.content-wrapper").append(data);
                }
              });

            });

            clearInterval(month_div_interval);
          }

        }, 500);




        $( document ).ready(function() {
          $.ajax({
            url:'/get/date/availability',
            type: "GET",
            dataType: "json",
            data: {
              "current_date": moment().format(),
            },
            async: true,
            success: function (data) {
              $("div.content-wrapper").append(data);
            }
          });
        });   




        $(document).on('click', 'button#unavailable_btn', function(){
          $("div#ajax_loader").show();
          that = $(this);
          $.ajax({
				url:'/admin/date/availability',
				type: "POST",
				dataType: "json",
				data: {
					"start_date": selected_dates['start'],
					"end_date": selected_dates['end'],
					"availability": 0
				},
				async: true,
				success: function (data) {
				  	$("div.content-wrapper").append(data);
					$("div#ajax_loader").hide();
              	}
         	});
          return false;
        });

         $(document).on('click', 'button#available_btn', function(){
         					$("div#ajax_loader").show();

          that = $(this);
          $.ajax({
              url:'/admin/date/availability',
              type: "POST",
              dataType: "json",
              data: {
                  "start_date": selected_dates['start'],
                  "end_date": selected_dates['end'],
                  "availability": 1
              },
              async: true,
              success: function (data) {
                	$("div.content-wrapper").append(data);
					$("div#ajax_loader").hide();
              }
          });
          return false;
        });
      </script>
		</div>
	</div>

	<script src="/bundles/wicrewcore/javascript/jquery-ui.js"></script>
	<script src="/bundles/wicrewcore/javascript/core.js"></script>
	<script src="/bundles/wicrewcore/javascript/jquery.fancybox.min.js"></script>
	<script src="/bundles/wicrewcore/javascript/jquery.dl_timepicker.min.js"></script>
	<script src="/bundles/wicrewcore/javascript/jquery.mask.min.js"></script>


	<script type="text/javascript">
		$(function () {
const toggles = document.querySelectorAll('.checkbox-switch input[type="checkbox"]');
for (i = 0; i < toggles.length; i ++) {
toggles[i].addEventListener('change', function () {
const toggle = this;
const newValue = this.checked;
const oldValue = ! newValue;
const propertyName = this.closest('.checkbox-switch').dataset.propertyname;

const toggleUrl = "/admin/?action=edit&entity=DateAvailability&view=list" + "&id=" + this.closest('tr').dataset.id + "&property=" + propertyName + "&newValue=" + newValue.toString();

let toggleRequest = $.ajax({type: "GET", url: toggleUrl, data: {}});

toggleRequest.done(function (result) {});

toggleRequest.fail(function () { // in case of error, restore the original value and disable the toggle
toggle.checked = oldValue;
toggle.disabled = true;
toggle.closest('.checkbox-switch').classList.add('disabled');
});
});
}

$('.action-delete').on('click', function (e) {
e.preventDefault();
const id = $(this).parents('tr').first().data('id');

$('#modal-delete').modal({backdrop: true, keyboard: true}).off('click', '#modal-delete-button').on('click', '#modal-delete-button', function () {
let deleteForm = $('#delete-form');
deleteForm.attr('action', deleteForm.attr('action').replace('__id__', id));
deleteForm.trigger('submit');
});
});


});
	</script>


	<div id="sfwdte21f3a" class="sf-toolbar sf-display-none"></div>
	<script nonce="d0141d616dd4bb96b57fa9054dae3ec4">
		/*<![CDATA[*/
Sfjs = (function () {
"use strict";
if ('classList' in document.documentElement) {
var hasClass = function (el, cssClass) {
return el.classList.contains(cssClass);
};
var removeClass = function (el, cssClass) {
el.classList.remove(cssClass);
};
var addClass = function (el, cssClass) {
el.classList.add(cssClass);
};
var toggleClass = function (el, cssClass) {
el.classList.toggle(cssClass);
};
} else {
var hasClass = function (el, cssClass) {
return el.className.match(new RegExp('\\b' + cssClass + '\\b'));
};
var removeClass = function (el, cssClass) {
el.className = el.className.replace(new RegExp('\\b' + cssClass + '\\b'), ' ');
};
var addClass = function (el, cssClass) {
if (! hasClass(el, cssClass)) {
el.className += " " + cssClass;
}
};
var toggleClass = function (el, cssClass) {
hasClass(el, cssClass) ? removeClass(el, cssClass) : addClass(el, cssClass);
};
}
var noop = function () {};
var profilerStorageKey = 'symfony/profiler/';
var request = function (url, onSuccess, onError, payload, options) {
var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
options = options || {};
options.maxTries = options.maxTries || 0;
xhr.open(options.method || 'GET', url, true);
xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
xhr.onreadystatechange = function (state) {
if (4 !== xhr.readyState) {
return null;
}
if (xhr.status == 404 && options.maxTries > 1) {
setTimeout(function () {
options.maxTries --;
request(url, onSuccess, onError, payload, options);
}, 1000);
return null;
}
if (200 === xhr.status) {
(onSuccess || noop)(xhr);
} else {
(onError || noop)(xhr);
}
};
xhr.send(payload || '');
};
var getPreference = function (name) {
if (!window.localStorage) {
return null;
}
return localStorage.getItem(profilerStorageKey + name);
};
var setPreference = function (name, value) {
if (!window.localStorage) {
return null;
}
localStorage.setItem(profilerStorageKey + name, value);
};
var requestStack = [];
var extractHeaders = function (xhr, stackElement) {
/* Here we avoid to call xhr.getResponseHeader in order to */
/* prevent polluting the console with CORS security errors */
var allHeaders = xhr.getAllResponseHeaders();
var ret;
if (ret = allHeaders.match(/^x-debug-token:\s+(.*)$/im)) {
stackElement.profile = ret[1];
}
if (ret = allHeaders.match(/^x-debug-token-link:\s+(.*)$/im)) {
stackElement.profilerUrl = ret[1];
}
if (ret = allHeaders.match(/^Symfony-Debug-Toolbar-Replace:\s+(.*)$/im)) {
stackElement.toolbarReplaceFinished = false;
stackElement.toolbarReplace = '1' === ret[1];
}
};
var successStreak = 4;
var pendingRequests = 0;
var renderAjaxRequests = function () {
var requestCounter = document.querySelector('.sf-toolbar-ajax-request-counter');
if (! requestCounter) {
return;
}
requestCounter.textContent = requestStack.length;
var infoSpan = document.querySelector(".sf-toolbar-ajax-info");
if (infoSpan) {
infoSpan.textContent = requestStack.length + ' AJAX request' + (
requestStack.length !== 1 ? 's' : ''
);
}
var ajaxToolbarPanel = document.querySelector('.sf-toolbar-block-ajax');
if (requestStack.length) {
ajaxToolbarPanel.style.display = 'block';
} else {
ajaxToolbarPanel.style.display = 'none';
}
if (pendingRequests > 0) {
addClass(ajaxToolbarPanel, 'sf-ajax-request-loading');
} else if (successStreak < 4) {
addClass(ajaxToolbarPanel, 'sf-toolbar-status-red');
removeClass(ajaxToolbarPanel, 'sf-ajax-request-loading');
} else {
removeClass(ajaxToolbarPanel, 'sf-ajax-request-loading');
removeClass(ajaxToolbarPanel, 'sf-toolbar-status-red');
}
};
var startAjaxRequest = function (index) {
var tbody = document.querySelector('.sf-toolbar-ajax-request-list');
if (! tbody) {
return;
}
var nbOfAjaxRequest = tbody.rows.length;
if (nbOfAjaxRequest >= 100) {
tbody.deleteRow(0);
}
var request = requestStack[index];
pendingRequests++;
var row = document.createElement('tr');
request.DOMNode = row;
var requestNumberCell = document.createElement('td');
requestNumberCell.textContent = index + 1;
row.appendChild(requestNumberCell);
var profilerCell = document.createElement('td');
profilerCell.textContent = 'n/a';
row.appendChild(profilerCell);
var methodCell = document.createElement('td');
methodCell.textContent = request.method;
row.appendChild(methodCell);
var typeCell = document.createElement('td');
typeCell.textContent = request.type;
row.appendChild(typeCell);
var statusCodeCell = document.createElement('td');
var statusCode = document.createElement('span');
statusCode.textContent = 'n/a';
statusCodeCell.appendChild(statusCode);
row.appendChild(statusCodeCell);
var pathCell = document.createElement('td');
pathCell.className = 'sf-ajax-request-url';
if ('GET' === request.method) {
var pathLink = document.createElement('a');
pathLink.setAttribute('href', request.url);
pathLink.textContent = request.url;
pathCell.appendChild(pathLink);
} else {
pathCell.textContent = request.url;
} pathCell.setAttribute('title', request.url);
row.appendChild(pathCell);
var durationCell = document.createElement('td');
durationCell.className = 'sf-ajax-request-duration';
durationCell.textContent = 'n/a';
row.appendChild(durationCell);
request.liveDurationHandle = setInterval(function () {
durationCell.textContent = (new Date() - request.start) + 'ms';
}, 100);
row.className = 'sf-ajax-request sf-ajax-request-loading';
tbody.insertBefore(row, null);
var toolbarInfo = document.querySelector('.sf-toolbar-block-ajax .sf-toolbar-info');
toolbarInfo.scrollTop = toolbarInfo.scrollHeight;
renderAjaxRequests();
};
var finishAjaxRequest = function (index) {
var request = requestStack[index];
clearInterval(request.liveDurationHandle);
if (! request.DOMNode) {
return;
}
if (request.toolbarReplace && ! request.toolbarReplaceFinished && request.profile) { /* Flag as complete because finishAjaxRequest can be called multiple times. */
request.toolbarReplaceFinished = true; /* Search up through the DOM to find the toolbar's container ID. */
for (var elem = request.DOMNode; elem && elem !== document; elem = elem.parentNode) {
if (elem.id.match(/^sfwdt/)) {
Sfjs.loadToolbar(elem.id.replace(/^sfwdt/, ''), request.profile);
break;
}
}
}
pendingRequests--;
var row = request.DOMNode; /* Unpack the children from the row */
var profilerCell = row.children[1];
var methodCell = row.children[2];
var statusCodeCell = row.children[4];
var statusCodeElem = statusCodeCell.children[0];
var durationCell = row.children[6];
if (request.error) {
row.className = 'sf-ajax-request sf-ajax-request-error';
methodCell.className = 'sf-ajax-request-error';
successStreak = 0;
} else {
row.className = 'sf-ajax-request sf-ajax-request-ok';
successStreak++;
}
if (request.statusCode) {
if (request.statusCode < 300) {
statusCodeElem.setAttribute('class', 'sf-toolbar-status');
} else if (request.statusCode < 400) {
statusCodeElem.setAttribute('class', 'sf-toolbar-status sf-toolbar-status-yellow');
} else {
statusCodeElem.setAttribute('class', 'sf-toolbar-status sf-toolbar-status-red');
} statusCodeElem.textContent = request.statusCode;
} else {
statusCodeElem.setAttribute('class', 'sf-toolbar-status sf-toolbar-status-red');
}
if (request.duration) {
durationCell.textContent = request.duration + 'ms';
}
if (request.profilerUrl) {
profilerCell.textContent = '';
var profilerLink = document.createElement('a');
profilerLink.setAttribute('href', request.statusCode < 400 ? request.profilerUrl : request.profilerUrl + '?panel=exception');
profilerLink.textContent = request.profile;
profilerCell.appendChild(profilerLink);
}
renderAjaxRequests();
};
var addEventListener;
var el = document.createElement('div');
if (!('addEventListener' in el)) {
addEventListener = function (element, eventName, callback) {
element.attachEvent('on' + eventName, callback);
};
} else {
addEventListener = function (element, eventName, callback) {
element.addEventListener(eventName, callback, false);
};
}
if (window.fetch && window.fetch.polyfill === undefined) {
var oldFetch = window.fetch;
window.fetch = function () {
var promise = oldFetch.apply(this, arguments);
var url = arguments[0];
var params = arguments[1];
var paramType = Object.prototype.toString.call(arguments[0]);
if (paramType === '[object Request]') {
url = arguments[0].url;
params = {
method: arguments[0].method,
credentials: arguments[0].credentials,
headers: arguments[0].headers,
mode: arguments[0].mode,
redirect: arguments[0].redirect
};
} else {
url = String(url);
}
if (! url.match(new RegExp("^\/((index|app(_[\\w]+)?)\\.php\/)?_wdt"))) {
var method = 'GET';
if (params && params.method !== undefined) {
method = params.method;
}
var stackElement = {
error: false,
url: url,
method: method,
type: 'fetch',
start: new Date()
};
var idx = requestStack.push(stackElement) - 1;
promise.then(function (r) {
stackElement.duration = new Date() - stackElement.start;
stackElement.error = r.status < 200 || r.status >= 400;
stackElement.statusCode = r.status;
stackElement.profile = r.headers.get('x-debug-token');
stackElement.profilerUrl = r.headers.get('x-debug-token-link');
stackElement.toolbarReplaceFinished = false;
stackElement.toolbarReplace = '1' === r.headers.get('Symfony-Debug-Toolbar-Replace');
finishAjaxRequest(idx);
}, function (e) {
stackElement.error = true;
finishAjaxRequest(idx);
});
startAjaxRequest(idx);
}
return promise;
};
}
if (window.XMLHttpRequest && XMLHttpRequest.prototype.addEventListener) {
var proxied = XMLHttpRequest.prototype.open;
XMLHttpRequest.prototype.open = function (method, url, async, user, pass) {
var self = this; /* prevent logging AJAX calls to static and inline files, like templates */
var path = url;
if (url.substr(0, 1) === '/') {
if (0 === url.indexOf('')) {
path = url.substr(0);
}
} else if (0 === url.indexOf('http\u003A\/\/127.0.0.1\u003A8000')) {
path = url.substr(21);
}
if (! path.match(new RegExp("^\/((index|app(_[\\w]+)?)\\.php\/)?_wdt"))) {
var stackElement = {
error: false,
url: url,
method: method,
type: 'xhr',
start: new Date()
};
var idx = requestStack.push(stackElement) - 1;
this.addEventListener('readystatechange', function () {
if (self.readyState == 4) {
stackElement.duration = new Date() - stackElement.start;
stackElement.error = self.status < 200 || self.status >= 400;
stackElement.statusCode = self.status;
extractHeaders(self, stackElement);
finishAjaxRequest(idx);
}
}, false);
startAjaxRequest(idx);
}
proxied.apply(this, Array.prototype.slice.call(arguments));
};
}
return {
hasClass: hasClass,
removeClass: removeClass,
addClass: addClass,
toggleClass: toggleClass,
getPreference: getPreference,
setPreference: setPreference,
addEventListener: addEventListener,
request: request,
renderAjaxRequests: renderAjaxRequests,
load: function (selector, url, onSuccess, onError, options) {
var el = document.getElementById(selector);
if (el && el.getAttribute('data-sfurl') !== url) {
request(url, function (xhr) {
el.innerHTML = xhr.responseText;
el.setAttribute('data-sfurl', url);
removeClass(el, 'loading');
var pending = pendingRequests;
for (var i = 0; i < requestStack.length; i++) {
startAjaxRequest(i);
if (requestStack[i].duration) {
finishAjaxRequest(i);
}
} /* Revert the pending state in case there was a start called without a finish above. */
pendingRequests = pending;
(onSuccess || noop)(xhr, el);
}, function (xhr) {
(onError || noop)(xhr, el);
}, '', options);
}
return this;
},
loadToolbar: function (token, newToken) {
newToken = (newToken || token);
this.load('sfwdt' + token, '\/_wdt\/xxxxxx'.replace(/xxxxxx/, newToken), function (xhr, el) { /* Evaluate in global scope scripts embedded inside the toolbar */
var i,
scripts = [].slice.call(el.querySelectorAll('script'));
for (i = 0; i < scripts.length; ++ i) {
eval.call({}, scripts[i].firstChild.nodeValue);
}
el.style.display = -1 !== xhr.responseText.indexOf('sf-toolbarreset') ? 'block' : 'none';
if (el.style.display == 'none') {
return;
}
if (getPreference('toolbar/displayState') == 'none') {
document.getElementById('sfToolbarMainContent-' + newToken).style.display = 'none';
document.getElementById('sfToolbarClearer-' + newToken).style.display = 'none';
document.getElementById('sfMiniToolbar-' + newToken).style.display = 'block';
} else {
document.getElementById('sfToolbarMainContent-' + newToken).style.display = 'block';
document.getElementById('sfToolbarClearer-' + newToken).style.display = 'block';
document.getElementById('sfMiniToolbar-' + newToken).style.display = 'none';
} /* Handle toolbar-info position */
var toolbarBlocks = [].slice.call(el.querySelectorAll('.sf-toolbar-block'));
for (i = 0; i < toolbarBlocks.length; ++ i) {
toolbarBlocks[i].onmouseover = function () {
var toolbarInfo = this.querySelectorAll('.sf-toolbar-info')[0];
var pageWidth = document.body.clientWidth;
var elementWidth = toolbarInfo.offsetWidth;
var leftValue = (elementWidth + this.offsetLeft) - pageWidth;
var rightValue = (elementWidth + (pageWidth - this.offsetLeft)) - pageWidth; /* Reset right and left value, useful on window resize */
toolbarInfo.style.right = '';
toolbarInfo.style.left = '';
if (elementWidth > pageWidth) {
toolbarInfo.style.left = 0;
} else if (leftValue > 0 && rightValue > 0) {
toolbarInfo.style.right = (rightValue * -1) + 'px';
} else if (leftValue < 0) {
toolbarInfo.style.left = 0;
} else {
toolbarInfo.style.right = '0px';
}
};
}
addEventListener(document.getElementById('sfToolbarHideButton-' + newToken), 'click', function (event) {
event.preventDefault();
var p = this.parentNode;
p.style.display = 'none';
(p.previousElementSibling || p.previousSibling).style.display = 'none';
document.getElementById('sfMiniToolbar-' + newToken).style.display = 'block';
setPreference('toolbar/displayState', 'none');
});
addEventListener(document.getElementById('sfToolbarMiniToggler-' + newToken), 'click', function (event) {
event.preventDefault();
var elem = this.parentNode;
if (elem.style.display == 'none') {
document.getElementById('sfToolbarMainContent-' + newToken).style.display = 'none';
document.getElementById('sfToolbarClearer-' + newToken).style.display = 'none';
elem.style.display = 'block';
} else {
document.getElementById('sfToolbarMainContent-' + newToken).style.display = 'block';
document.getElementById('sfToolbarClearer-' + newToken).style.display = 'block';
elem.style.display = 'none'
} setPreference('toolbar/displayState', 'block');
});
renderAjaxRequests();
addEventListener(document.querySelector('.sf-toolbar-block-ajax'), 'mouseenter', function (event) {
var elem = document.querySelector('.sf-toolbar-block-ajax .sf-toolbar-info');
elem.scrollTop = elem.scrollHeight;
});
addEventListener(document.querySelector('.sf-toolbar-block-ajax > .sf-toolbar-icon'), 'click', function (event) {
event.preventDefault();
toggleClass(this.parentNode, 'hover');
});
var dumpInfo = document.querySelector('.sf-toolbar-block-dump .sf-toolbar-info');
if (null !== dumpInfo) {
addEventListener(dumpInfo, 'sfbeforedumpcollapse', function () {
dumpInfo.style.minHeight = dumpInfo.getBoundingClientRect().height + 'px';
});
addEventListener(dumpInfo, 'mouseleave', function () {
dumpInfo.style.minHeight = '';
});
}
}, function (xhr) {
if (xhr.status !== 0) {
var sfwdt = document.getElementById('sfwdt' + token);
sfwdt.innerHTML = '\                                <div class="sf-toolbarreset">\                                    <div class="sf-toolbar-icon"><svg width="26" height="28" xmlns="http://www.w3.org/2000/svg" version="1.1" x="0px" y="0px" viewBox="0 0 26 28" enable-background="new 0 0 26 28" xml:space="preserve"><path fill="#FFFFFF" d="M13 0C5.8 0 0 5.8 0 13c0 7.2 5.8 13 13 13c7.2 0 13-5.8 13-13C26 5.8 20.2 0 13 0z M20 7.5 c-0.6 0-1-0.3-1-0.9c0-0.2 0-0.4 0.2-0.6c0.1-0.3 0.2-0.3 0.2-0.4c0-0.3-0.5-0.4-0.7-0.4c-2 0.1-2.5 2.7-2.9 4.8l-0.2 1.1 c1.1 0.2 1.9 0 2.4-0.3c0.6-0.4-0.2-0.8-0.1-1.3C18 9.2 18.4 9 18.7 8.9c0.5 0 0.8 0.5 0.8 1c0 0.8-1.1 2-3.3 1.9 c-0.3 0-0.5 0-0.7-0.1L15 14.1c-0.4 1.7-0.9 4.1-2.6 6.2c-1.5 1.8-3.1 2.1-3.8 2.1c-1.3 0-2.1-0.6-2.2-1.6c0-0.9 0.8-1.4 1.3-1.4 c0.7 0 1.2 0.5 1.2 1.1c0 0.5-0.2 0.6-0.4 0.7c-0.1 0.1-0.3 0.2-0.3 0.4c0 0.1 0.1 0.3 0.4 0.3c0.5 0 0.9-0.3 1.2-0.5 c1.3-1 1.7-2.9 2.4-6.2l0.1-0.8c0.2-1.1 0.5-2.3 0.8-3.5c-0.9-0.7-1.4-1.5-2.6-1.8c-0.8-0.2-1.3 0-1.7 0.4C8.4 10 8.6 10.7 9 11.1 l0.7 0.7c0.8 0.9 1.3 1.7 1.1 2.7c-0.3 1.6-2.1 2.8-4.3 2.1c-1.9-0.6-2.2-1.9-2-2.7c0.2-0.6 0.7-0.8 1.2-0.6 c0.5 0.2 0.7 0.8 0.6 1.3c0 0.1 0 0.1-0.1 0.3C6 15 5.9 15.2 5.9 15.3c-0.1 0.4 0.4 0.7 0.8 0.8c0.8 0.3 1.7-0.2 1.9-0.9 c0.2-0.6-0.2-1.1-0.4-1.2l-0.8-0.9c-0.4-0.4-1.2-1.5-0.8-2.8c0.2-0.5 0.5-1 0.9-1.4c1-0.7 2-0.8 3-0.6c1.3 0.4 1.9 1.2 2.8 1.9 c0.5-1.3 1.1-2.6 2-3.8c0.9-1 2-1.7 3.3-1.8C20 4.8 21 5.4 21 6.3C21 6.7 20.8 7.5 20 7.5z"/></svg></div>\                                    An error occurred while loading the web debug toolbar. <a href="\/_profiler\/' + newToken + '>Open the web profiler.</a>\                                </div>\                            ';
sfwdt.setAttribute('class', 'sf-toolbar sf-error-toolbar');
}
}, {maxTries: 5});
return this;
},
toggle: function (selector, elOn, elOff) {
var tmp = elOn.style.display,
el = document.getElementById(selector);
elOn.style.display = elOff.style.display;
elOff.style.display = tmp;
if (el) {
el.style.display = 'none' === tmp ? 'none' : 'block';
}
return this;
},
createTabs: function () {
var tabGroups = document.querySelectorAll('.sf-tabs:not([data-processed=true])'); /* create the tab navigation for each group of tabs */
for (var i = 0; i < tabGroups.length; i++) {
var tabs = tabGroups[i].querySelectorAll(':scope > .tab');
var tabNavigation = document.createElement('ul');
tabNavigation.className = 'tab-navigation';
var selectedTabId = 'tab-' + i + '-0'; /* select the first tab by default */
for (var j = 0; j < tabs.length; j++) {
var tabId = 'tab-' + i + '-' + j;
var tabTitle = tabs[j].querySelector('.tab-title').innerHTML;
var tabNavigationItem = document.createElement('li');
tabNavigationItem.setAttribute('data-tab-id', tabId);
if (hasClass(tabs[j], 'active')) {
selectedTabId = tabId;
}
if (hasClass(tabs[j], 'disabled')) {
addClass(tabNavigationItem, 'disabled');
}
tabNavigationItem.innerHTML = tabTitle;
tabNavigation.appendChild(tabNavigationItem);
var tabContent = tabs[j].querySelector('.tab-content');
tabContent.parentElement.setAttribute('id', tabId);
}
tabGroups[i].insertBefore(tabNavigation, tabGroups[i].firstChild);
addClass(document.querySelector('[data-tab-id="' + selectedTabId + '"]'), 'active');
} /* display the active tab and add the 'click' event listeners */
for (i = 0; i < tabGroups.length; i++) {
tabNavigation = tabGroups[i].querySelectorAll(':scope > .tab-navigation li');
for (j = 0; j < tabNavigation.length; j ++) {
tabId = tabNavigation[j].getAttribute('data-tab-id');
document.getElementById(tabId).querySelector('.tab-title').className = 'hidden';
if (hasClass(tabNavigation[j], 'active')) {
document.getElementById(tabId).className = 'block';
} else {
document.getElementById(tabId).className = 'hidden';
}
tabNavigation[j].addEventListener('click', function (e) {
var activeTab = e.target || e.srcElement;
/* needed because when the tab contains HTML contents, user can click */
/* on any of those elements instead of their parent '<li>' element */
while (activeTab.tagName.toLowerCase() !== 'li') {
activeTab = activeTab.parentNode;
} /* get the full list of tabs through the parent of the active tab element */
var tabNavigation = activeTab.parentNode.children;
for (var k = 0; k < tabNavigation.length; k++) {
var tabId = tabNavigation[k].getAttribute('data-tab-id');
document.getElementById(tabId).className = 'hidden';
removeClass(tabNavigation[k], 'active');
}
addClass(activeTab, 'active');
var activeTabId = activeTab.getAttribute('data-tab-id');
document.getElementById(activeTabId).className = 'block';
});
}
tabGroups[i].setAttribute('data-processed', 'true');
}
},
createToggles: function () {
var toggles = document.querySelectorAll('.sf-toggle:not([data-processed=true])');
for (var i = 0; i < toggles.length; i++) {
var elementSelector = toggles[i].getAttribute('data-toggle-selector');
var element = document.querySelector(elementSelector);
addClass(element, 'sf-toggle-content');
if (toggles[i].hasAttribute('data-toggle-initial') && toggles[i].getAttribute('data-toggle-initial') == 'display') {
addClass(toggles[i], 'sf-toggle-on');
addClass(element, 'sf-toggle-visible');
} else {
addClass(toggles[i], 'sf-toggle-off');
addClass(element, 'sf-toggle-hidden');
} addEventListener(toggles[i], 'click', function (e) {
e.preventDefault();
if ('' !== window.getSelection().toString()) { /* Don't do anything on text selection */
return;
}
var toggle = e.target || e.srcElement;
/* needed because when the toggle contains HTML contents, user can click */
/* on any of those elements instead of their parent '.sf-toggle' element */
while (!hasClass(toggle, 'sf-toggle')) {
toggle = toggle.parentNode;
}
var element = document.querySelector(toggle.getAttribute('data-toggle-selector'));
toggleClass(toggle, 'sf-toggle-on');
toggleClass(toggle, 'sf-toggle-off');
toggleClass(element, 'sf-toggle-hidden');
toggleClass(element, 'sf-toggle-visible'); /* the toggle doesn't change its contents when clicking on it */
if (! toggle.hasAttribute('data-toggle-alt-content')) {
return;
}
if (! toggle.hasAttribute('data-toggle-original-content')) {
toggle.setAttribute('data-toggle-original-content', toggle.innerHTML);
}
var currentContent = toggle.innerHTML;
var originalContent = toggle.getAttribute('data-toggle-original-content');
var altContent = toggle.getAttribute('data-toggle-alt-content');
toggle.innerHTML = currentContent !== altContent ? altContent : originalContent;
}); /* Prevents from disallowing clicks on links inside toggles */
var toggleLinks = toggles[i].querySelectorAll('a');
for (var j = 0; j < toggleLinks.length; j++) {
addEventListener(toggleLinks[j], 'click', function (e) {
e.stopPropagation();
});
}
toggles[i].setAttribute('data-processed', 'true');
}
},
createFilters: function () {
document.querySelectorAll('[data-filters] [data-filter]').forEach(function (filter) {
var filters = filter.closest('[data-filters]'),
type = 'choice',
name = filter.dataset.filter,
ucName = name.charAt(0).toUpperCase() + name.slice(1),
list = document.createElement('ul'),
values = filters.dataset['filter' + ucName] || filters.querySelectorAll('[data-filter-' + name + ']'),
labels = {},
defaults = null,
indexed = {},
processed = {};
if (typeof values === 'string') {
type = 'level';
labels = values.split(',');
values = values.toLowerCase().split(',');
defaults = values.length - 1;
}
addClass(list, 'filter-list');
addClass(list, 'filter-list-' + type);
values.forEach(function (value, i) {
if (value instanceof HTMLElement) {
value = value.dataset['filter' + ucName];
}
if (value in processed) {
return;
}
var option = document.createElement('li'),
label = i in labels ? labels[i] : value,
active = false,
matches;
if ('' === label) {
option.innerHTML = '<em>(none)</em>';
} else {
option.innerText = label;
} option.dataset.filter = value;
option.setAttribute('title', 1 === (matches = filters.querySelectorAll('[data-filter-' + name + '="' + value + '"]').length) ? 'Matches 1 row' : 'Matches ' + matches + ' rows');
indexed[value] = i;
list.appendChild(option);
addEventListener(option, 'click', function () {
if ('choice' === type) {
filters.querySelectorAll('[data-filter-' + name + ']').forEach(function (row) {
if (option.dataset.filter === row.dataset['filter' + ucName]) {
toggleClass(row, 'filter-hidden-' + name);
}
});
toggleClass(option, 'active');
} else if ('level' === type) {
if (i === this.parentNode.querySelectorAll('.active').length - 1) {
return;
}
this.parentNode.querySelectorAll('li').forEach(function (currentOption, j) {
if (j <= i) {
addClass(currentOption, 'active');
if (i === j) {
addClass(currentOption, 'last-active');
} else {
removeClass(currentOption, 'last-active');
}
} else {
removeClass(currentOption, 'active');
removeClass(currentOption, 'last-active');
}
});
filters.querySelectorAll('[data-filter-' + name + ']').forEach(function (row) {
if (i < indexed[row.dataset['filter' + ucName]]) {
addClass(row, 'filter-hidden-' + name);
} else {
removeClass(row, 'filter-hidden-' + name);
}
});
}
});
if ('choice' === type) {
active = null === defaults || 0 <= defaults.indexOf(value);
} else if ('level' === type) {
active = i <= defaults;
if (active && i === defaults) {
addClass(option, 'last-active');
}
}
if (active) {
addClass(option, 'active');
} else {
filters.querySelectorAll('[data-filter-' + name + '="' + value + '"]').forEach(function (row) {
toggleClass(row, 'filter-hidden-' + name);
});
}
processed[value] = true;
});
if (1 < list.childNodes.length) {
filter.appendChild(list);
filter.dataset.filtered = '';
}
});
}
};
})();
Sfjs.addEventListener(document, 'DOMContentLoaded', function () {
Sfjs.createTabs();
Sfjs.createToggles();
}); /*]]>*/
	</script>
	<style nonce="aaccd1dbbd856c19a7104a2bac93b9f6">
		.sf-minitoolbar {
			background-color: #222;
			border-top-left-radius: 4px;
			bottom: 0;
			box-sizing: border-box;
			display: none;
			height: 36px;
			padding: 6px;
			position: fixed;
			right: 0;
			z-index: 99999;
		}
		.sf-minitoolbar a {
			display: block;
		}
		.sf-minitoolbar svg,
		.sf-minitoolbar img {
			max-height: 24px;
			max-width: 24px;
			display: inline;
		}
		.sf-toolbar-clearer {
			clear: both;
			height: 36px;
		}
		.sf-display-none {
			display: none;
		}
		.sf-toolbarreset * {
			box-sizing: content-box;
			vertical-align: baseline;
			letter-spacing: normal;
			width: auto;
		}
		.sf-toolbarreset {
			background-color: #222;
			bottom: 0;
			box-shadow: 0 -1px 0 rgba(0, 0, 0, 0.2);
			color: #EEE;
			font: 11px Arial, sans-serif;
			left: 0;
			margin: 0;
			padding: 0 36px 0 0;
			position: fixed;
			right: 0;
			text-align: left;
			text-transform: none;
			z-index: 99999;
			direction: ltr; /* neutralize the aliasing defined by external CSS styles */
			-webkit-font-smoothing: subpixel-antialiased;
			-moz-osx-font-smoothing: auto;
		}
		.sf-toolbarreset abbr {
			border: dashed #777;
			border-width: 0 0 1px;
		}
		.sf-toolbarreset svg,
		.sf-toolbarreset img {
			height: 20px;
			width: 20px;
			display: inline-block;
		}
		.sf-toolbarreset .hide-button {
			background: #444;
			display: block;
			position: absolute;
			top: 0;
			right: 0;
			width: 36px;
			height: 36px;
			cursor: pointer;
			text-align: center;
		}
		.sf-toolbarreset .hide-button svg {
			max-height: 18px;
			margin-top: 10px;
		}
		.sf-toolbar-block {
			cursor: default;
			display: block;
			float: left;
			height: 36px;
			margin-right: 0;
			white-space: nowrap;
			max-width: 15%;
		}
		.sf-toolbar-block > a,
		.sf-toolbar-block > a:hover {
			display: block;
			text-decoration: none;
			color: inherit;
		}
		.sf-toolbar-block span {
			display: inline-block;
		}
		.sf-toolbar-block .sf-toolbar-value {
			color: #F5F5F5;
			font-size: 13px;
			line-height: 36px;
			padding: 0;
		}
		.sf-toolbar-block .sf-toolbar-label,
		.sf-toolbar-block .sf-toolbar-class-separator {
			color: #AAA;
			font-size: 12px;
		}
		.sf-toolbar-block .sf-toolbar-info {
			border-collapse: collapse;
			display: table;
			z-index: 100000;
		}
		.sf-toolbar-block hr {
			border-top: 1px solid #777;
			margin: 4px 0;
			padding-top: 4px;
		}
		.sf-toolbar-block .sf-toolbar-info-piece {
			/* this 'border-bottom' trick is needed because 'margin-bottom' doesn't work for table rows */
			border-bottom: solid transparent 3px;
			display: table-row;
		}
		.sf-toolbar-block .sf-toolbar-info-piece-additional,
		.sf-toolbar-block .sf-toolbar-info-piece-additional-detail {
			display: none;
		}
		.sf-toolbar-block .sf-toolbar-info-group {
			margin-bottom: 4px;
			padding-bottom: 2px;
			border-bottom: 1px solid #333333;
		}
		.sf-toolbar-block .sf-toolbar-info-group:last-child {
			margin-bottom: 0;
			padding-bottom: 0;
			border-bottom: none;
		}
		.sf-toolbar-block .sf-toolbar-info-piece .sf-toolbar-status {
			padding: 2px 5px;
			margin-bottom: 0;
		}
		.sf-toolbar-block .sf-toolbar-info-piece .sf-toolbar-status + .sf-toolbar-status {
			margin-left: 4px;
		}
		.sf-toolbar-block .sf-toolbar-info-piece:last-child {
			margin-bottom: 0;
		}
		div.sf-toolbar .sf-toolbar-block .sf-toolbar-info-piece a {
			color: #99CDD8;
			text-decoration: underline;
		}
		div.sf-toolbar .sf-toolbar-block a:hover {
			text-decoration: none;
		}
		.sf-toolbar-block .sf-toolbar-info-piece b {
			color: #AAA;
			display: table-cell;
			font-size: 11px;
			padding: 4px 8px 4px 0;
		}
		.sf-toolbar-block:not(.sf-toolbar-block-dump) .sf-toolbar-info-piece span {
			color: #F5F5F5;
		}
		.sf-toolbar-block .sf-toolbar-info-piece span {
			font-size: 12px;
		}
		.sf-toolbar-block .sf-toolbar-info {
			background-color: #444;
			bottom: 36px;
			color: #F5F5F5;
			display: none;
			padding: 9px 0;
			position: absolute;
		}
		.sf-toolbar-block .sf-toolbar-info:empty {
			visibility: hidden;
		}
		.sf-toolbar-block .sf-toolbar-status {
			display: inline-block;
			color: #FFF;
			background-color: #666;
			padding: 3px 6px;
			margin-bottom: 2px;
			vertical-align: middle;
			min-width: 15px;
			min-height: 13px;
			text-align: center;
		}
		.sf-toolbar-block .sf-toolbar-status-green {
			background-color: #4F805D;
		}
		.sf-toolbar-block .sf-toolbar-status-red {
			background-color: #B0413E;
		}
		.sf-toolbar-block .sf-toolbar-status-yellow {
			background-color: #A46A1F;
		}
		.sf-toolbar-block.sf-toolbar-status-green {
			background-color: #4F805D;
			color: #FFF;
		}
		.sf-toolbar-block.sf-toolbar-status-red {
			background-color: #B0413E;
			color: #FFF;
		}
		.sf-toolbar-block.sf-toolbar-status-yellow {
			background-color: #A46A1F;
			color: #FFF;
		}
		.sf-toolbar-block-request .sf-toolbar-status {
			color: #FFF;
			display: inline-block;
			font-size: 14px;
			height: 36px;
			line-height: 36px;
			padding: 0 10px;
		}
		.sf-toolbar-block-request .sf-toolbar-info-piece a {
			text-decoration: none;
		}
		.sf-toolbar-block-request .sf-toolbar-info-piece a:hover {
			text-decoration: underline;
		}
		.sf-toolbar-block-request .sf-toolbar-redirection-status {
			font-weight: normal;
			padding: 2px 4px;
			line-height: 18px;
		}
		.sf-toolbar-block-request .sf-toolbar-info-piece span.sf-toolbar-redirection-method {
			font-size: 12px;
			height: 17px;
			line-height: 17px;
			margin-right: 5px;
		}
		.sf-toolbar-block-ajax .sf-toolbar-icon {
			cursor: pointer;
		}
		.sf-toolbar-status-green .sf-toolbar-label,
		.sf-toolbar-status-yellow .sf-toolbar-label,
		.sf-toolbar-status-red .sf-toolbar-label {
			color: #FFF;
		}
		.sf-toolbar-status-green svg path,
		.sf-toolbar-status-green svg .sf-svg-path,
		.sf-toolbar-status-red svg path,
		.sf-toolbar-status-red svg .sf-svg-path,
		.sf-toolbar-status-yellow svg path,
		.sf-toolbar-status-yellow svg .sf-svg-path {
			fill: #FFF;
		}
		.sf-toolbar-block-config svg path,
		.sf-toolbar-block-config svg .sf-svg-path {
			fill: #FFF;
		}
		.sf-toolbar-block .sf-toolbar-icon {
			display: block;
			height: 36px;
			padding: 0 7px;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.sf-toolbar-block-request .sf-toolbar-icon {
			padding-left: 0;
			padding-right: 0;
		}
		.sf-toolbar-block .sf-toolbar-icon img,
		.sf-toolbar-block .sf-toolbar-icon svg {
			border-width: 0;
			position: relative;
			top: 8px;
			vertical-align: baseline;
		}
		.sf-toolbar-block .sf-toolbar-icon img + span,
		.sf-toolbar-block .sf-toolbar-icon svg + span {
			margin-left: 4px;
		}
		.sf-toolbar-block-config .sf-toolbar-icon .sf-toolbar-value {
			margin-left: 4px;
		}
		.sf-toolbar-block:hover,
		.sf-toolbar-block.hover {
			position: relative;
		}
		.sf-toolbar-block:hover .sf-toolbar-icon,
		.sf-toolbar-block.hover .sf-toolbar-icon {
			background-color: #444;
			position: relative;
			z-index: 10002;
		}
		.sf-toolbar-block-ajax.hover .sf-toolbar-info {
			z-index: 10001;
		}
		.sf-toolbar-block:hover .sf-toolbar-info,
		.sf-toolbar-block.hover .sf-toolbar-info {
			display: block;
			padding: 10px;
			max-width: 480px;
			max-height: 480px;
			word-wrap: break-word;
			overflow: hidden;
			overflow-y: auto;
		}
		.sf-toolbar-info-piece b.sf-toolbar-ajax-info {
			color: #F5F5F5;
		}
		.sf-toolbar-ajax-requests {
			table-layout: auto;
			width: 100%;
		}
		.sf-toolbar-ajax-requests td {
			background-color: #444;
			border-bottom: 1px solid #777;
			color: #F5F5F5;
			font-size: 12px;
			padding: 4px;
		}
		.sf-toolbar-ajax-requests tr:last-child td {
			border-bottom: 0;
		}
		.sf-toolbar-ajax-requests th {
			background-color: #222;
			border-bottom: 0;
			color: #AAA;
			font-size: 11px;
			padding: 4px;
		}
		.sf-ajax-request-url {
			max-width: 250px;
			line-height: 9px;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		.sf-toolbar-ajax-requests .sf-ajax-request-url a {
			text-decoration: none;
		}
		.sf-toolbar-ajax-requests .sf-ajax-request-url a:hover {
			text-decoration: underline;
		}
		.sf-ajax-request-duration {
			text-align: right;
		}
		.sf-ajax-request-loading {
			animation: sf-blink 0.5s ease-in-out infinite;
		}
		@keyframes sf-blink {
			0% {
				background: #222;
			}
			50% {
				background: #444;
			}
			100% {
				background: #222;
			}
		}
		.sf-toolbar-block.sf-toolbar-block-dump .sf-toolbar-info {
			max-width: none;
			width: 100%;
			position: fixed;
			box-sizing: border-box;
			left: 0;
		}
		.sf-toolbar-block-dump pre.sf-dump {
			background-color: #222;
			border-color: #777;
			border-radius: 0;
			margin: 6px 0 12px;
		}
		.sf-toolbar-block-dump pre.sf-dump:last-child {
			margin-bottom: 0;
		}
		.sf-toolbar-block-dump pre.sf-dump .sf-dump-search-wrapper {
			margin-bottom: 5px;
		}
		.sf-toolbar-block-dump pre.sf-dump span.sf-dump-search-count {
			color: #333;
			font-size: 12px;
		}
		.sf-toolbar-block-dump .sf-toolbar-info-piece {
			display: block;
		}
		.sf-toolbar-block-dump .sf-toolbar-info-piece .sf-toolbar-file-line {
			color: #AAA;
			margin-left: 4px;
		}
		.sf-toolbar-block-dump .sf-toolbar-info img {
			display: none;
		} /* Responsive Design */
		.sf-toolbar-icon .sf-toolbar-label,
		.sf-toolbar-icon .sf-toolbar-value {
			display: none;
		}
		.sf-toolbar-block-config .sf-toolbar-icon .sf-toolbar-label {
			display: inline-block;
		} /* Legacy Design - these styles are maintained to make old panels look   a bit better on the new toolbar */
		.sf-toolbar-block .sf-toolbar-info-piece-additional-detail {
			color: #AAA;
			font-size: 12px;
		}
		.sf-toolbar-status-green .sf-toolbar-info-piece-additional-detail,
		.sf-toolbar-status-yellow .sf-toolbar-info-piece-additional-detail,
		.sf-toolbar-status-red .sf-toolbar-info-piece-additional-detail {
			color: #FFF;
		}
		@media(min-width: 768px) {
			.sf-toolbar-icon .sf-toolbar-label,
			.sf-toolbar-icon .sf-toolbar-value {
				display: inline;
			}
			.sf-toolbar-block .sf-toolbar-icon img,
			.sf-toolbar-block .sf-toolbar-icon svg {
				top: 6px;
			}
			.sf-toolbar-block-time .sf-toolbar-icon svg,
			.sf-toolbar-block-memory .sf-toolbar-icon svg {
				display: none;
			}
			.sf-toolbar-block-time .sf-toolbar-icon svg + span,
			.sf-toolbar-block-memory .sf-toolbar-icon svg + span {
				margin-left: 0;
			}
			.sf-toolbar-block .sf-toolbar-icon {
				padding: 0 10px;
			}
			.sf-toolbar-block-time .sf-toolbar-icon {
				padding-right: 5px;
			}
			.sf-toolbar-block-memory .sf-toolbar-icon {
				padding-left: 5px;
			}
			.sf-toolbar-block-request .sf-toolbar-icon {
				padding-left: 0;
				padding-right: 0;
			}
			.sf-toolbar-block-request .sf-toolbar-label {
				margin-left: 5px;
			}
			.sf-toolbar-block-request .sf-toolbar-status + svg {
				margin-left: 5px;
			}
			.sf-toolbar-block-request .sf-toolbar-icon svg + .sf-toolbar-label {
				margin-left: 0;
			}
			.sf-toolbar-block-request .sf-toolbar-label + .sf-toolbar-value {
				margin-right: 10px;
			}
			.sf-toolbar-block-request:hover .sf-toolbar-info {
				max-width: none;
			}
			.sf-toolbar-block .sf-toolbar-info-piece b {
				font-size: 12px;
			}
			.sf-toolbar-block .sf-toolbar-info-piece span {
				font-size: 13px;
			}
			.sf-toolbar-block-right {
				float: right;
				margin-left: 0;
				margin-right: 0;
			}
		}
		@media(min-width: 1024px) {
			.sf-toolbar-block .sf-toolbar-info-piece-additional,
			.sf-toolbar-block .sf-toolbar-info-piece-additional-detail {
				display: inline;
			}
			.sf-toolbar-block .sf-toolbar-info-piece-additional:empty,
			.sf-toolbar-block .sf-toolbar-info-piece-additional-detail:empty {
				display: none;
			}
		} /***** Error Toolbar *****/
		.sf-error-toolbar .sf-toolbarreset {
			background: #222;
			color: #f5f5f5;
			font: 13px / 36px Arial, sans-serif;
			height: 36px;
			padding: 0 15px;
			text-align: left;
		}
		.sf-error-toolbar .sf-toolbarreset svg {
			height: auto;
		}
		.sf-error-toolbar .sf-toolbarreset a {
			color: #99cdd8;
			margin-left: 5px;
			text-decoration: underline;
		}
		.sf-error-toolbar .sf-toolbarreset a:hover {
			text-decoration: none;
		}
    div.content-wrapper {
      display: block;
      background: #fff;
      padding: 20px;
      border-radius: 5px;
	  position: relative;
    }
	div#ajax_loader {display:none; position: fixed;width: 100%;background: #fffffff5;height: 100vh;top: 0;left: 0;bottom: 0;right: 0;z-index: 9999;}

	div#ajax_loader .spinner-border {
		margin: 0;
		position: absolute;
		top: 40%;
		left: 50%;
		width: 75px !important;
		height: 75px !important;
	}

		.sf-error-toolbar .sf-toolbarreset .sf-toolbar-icon {
			float: left;
			padding: 5px 0;
			margin-right: 10px;
		} /***** Media query print: Do not print the Toolbar. *****/
		@media print {
			.sf-toolbar {
				display: none;
			}
		}
    .button_container {display: flex;height: 42px;gap: 15px;margin-bottom: 21px;justify-content: flex-end;}

    .button_container button {
        border: 0px;
        padding: 10px;
        text-transform: uppercase;
        font-weight: bold;
        border-radius: 5px;
    }

    button#unavailable_btn {
        background: pink;
    }

    button#available_btn {
        background: mediumspringgreen;
    }

    .button_container button:hover {
        opacity: 0.8;
    }
	.sf-toolbarreset {
		display: none;
	}
	body.easyadmin.easyadmin-content-width-normal.easyadmin-sidebar-width-normal {
			background-color: #292D42 !important;
		}
		.sidebar-menu a,
		.sidebar-menu .fa{
			color: #fff !important;
		}
	</style>
	<script nonce="d0141d616dd4bb96b57fa9054dae3ec4">
		/*<![CDATA[*/
(function () {
Sfjs.loadToolbar('e21f3a');
})(); /*]]>*/
	</script>
</body></html>





       <?php
        exit;
    }
}
