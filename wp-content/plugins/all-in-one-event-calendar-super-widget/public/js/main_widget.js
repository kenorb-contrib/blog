timely.require(["scripts/calendar","scripts/common_scripts/frontend/common_frontend","domReady","jquery_timely","ai1ec_calendar"],function(e,t,n,r,i){var s=i.calendar_url,o=function(e){e.preventDefault(),e.stopImmediatePropagation(),r("div.ai1ec-popover").remove();var t="jsonp",n=r(this).closest(".timely-calendar"),s={request_type:t,ai1ec_doing_ajax:!0,ai1ec:f(n)};r(".ai1ec-loading").show(),r.ajax({url:r(this).attr("href"),dataType:t,data:s,method:"get",crossDomain:!0,success:function(t){r(".ai1ec-subscribe-container").hide();var n=r(e.target).closest(".timely-calendar").data("action");$containter=r(e.target).closest("#ai1ec-calendar-view"),$containter.html(t.html);var s=i.calendar_url,o=r(".ai1ec-calendar-link",$containter);o.addClass("ai1ec-load-view"),n&&(s.indexOf("?")===-1?s+="?":s+="&",s+="ai1ec=action~"+n),o.attr("href",s),r(".ai1ec-loading").hide(),timely.require(["pages/event"])}})};r("div.timely-calendar").on("click",".ai1ec-load-event[data-type=jsonp]",o);var u=function(e,t,n,r){var i=a(e),s=n.data(i);return s===undefined?t:(r?t.push(s):t.push(e+"~"+s),t)},a=function(e){return e.replace(/\W+(.)/g,function(e,t){return t.toUpperCase()})},f=function(e){var t=r(e),n=[];return n=u("action",n,t),n=u("cat_ids",n,t),n=u("auth_ids",n,t),n=u("tag_ids",n,t),n=u("exact_date",n,t),n=u("no_navigation",n,t),n=u("events_limit",n,t),n.join("|")};r("div.timely-calendar").each(function(e,t){var n=f(t),i={ai1ec_doing_ajax:!0,request_type:"jsonp",ai1ec:n};r.ajax({url:s,dataType:"jsonp",data:i,success:function(e){var n=r("<div/>",{id:"ai1ec-calendar-view-container","class":"ai1ec-calendar-view-container"}),i=r("<div/>",{"class":"timely ai1ec-calendar"}),s=r("<div/>",{id:"ai1ec-calendar-view-loading","class":"ai1ec-loading ai1ec-calendar-view-loading"}),o=r("<div/>",{id:"ai1ec-calendar-view","class":"ai1ec-calendar-view"});o.append(e.html),n.append(s).append(o),i.append(n).append(e.subscribe_buttons),r(t).append(i).data("added",!0),r(document).trigger("calendar_added.ai1ec")},error:function(e,t,n){window.alert("An error occurred while retrieving the calendar data.")}})}),r(document).on("calendar_added.ai1ec",function(){var n=r("div.timely-calendar"),s=0;for(var o=0;o<n.length;o++)n.eq(o).data("added")&&s++;n.length===s&&(t.are_event_listeners_attached()||t.start(),r.each(i.extension_urls,function(e,t){l(t)}),e.start(),r(document).trigger("page_ready.ai1ec"))}),r(document).on("initialize_view.ai1ec",function(){var e="",t=r("a.ai1ec-load-event").first();t!==undefined&&t.attr("data-type")!==undefined&&(e=t.attr("data-type"),r("div.ai1ec-event-avatar a").each(function(){r(this).addClass("ai1ec-load-event"),r(this).attr("data-type",e)}))});var l=function(e){var t=document.createElement("script");t.setAttribute("type","text/javascript"),t.setAttribute("src",e.url),t.setAttribute("id",e.id),t.setAttribute("data-added","true"),document.getElementsByTagName("head")[0].appendChild(t)}}),timely.define("main_widget",function(){});