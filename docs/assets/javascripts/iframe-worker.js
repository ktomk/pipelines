!function(e,r){for(var n in r)e[n]=r[n]}(window,function(e){var r={};function n(t){if(r[t])return r[t].exports;var o=r[t]={i:t,l:!1,exports:{}};return e[t].call(o.exports,o,o.exports,n),o.l=!0,o.exports}return n.m=e,n.c=r,n.d=function(e,r,t){n.o(e,r)||Object.defineProperty(e,r,{enumerable:!0,get:t})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,r){if(1&r&&(e=n(e)),8&r)return e;if(4&r&&"object"==typeof e&&e&&e.__esModule)return e;var t=Object.create(null);if(n.r(t),Object.defineProperty(t,"default",{enumerable:!0,value:e}),2&r&&"string"!=typeof e)for(var o in e)n.d(t,o,function(r){return e[r]}.bind(null,o));return t},n.n=function(e){var r=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(r,"a",r),r},n.o=function(e,r){return Object.prototype.hasOwnProperty.call(e,r)},n.p="",n(n.s=0)}([function(e,r,n){"use strict";Object.defineProperty(r,"__esModule",{value:!0});var t=n(1);window.IFrameWorker=t.IFrameWorker,"file:"===location.protocol&&(window.Worker=t.IFrameWorker)},function(e,r,n){"use strict";Object.defineProperty(r,"__esModule",{value:!0}),r.IFrameWorker=void 0;var t=n(2);var o=function(){function e(e,r){var n=this;if(this.url=e,this.onerror=null,this.onmessage=null,this.onmessageerror=null,this.handleMessage=function(e){e.source===n.worker&&(e.stopImmediatePropagation(),n.dispatchEvent(new MessageEvent("message",{data:e.data})),n.onmessage&&n.onmessage(e))},this.handleError=function(e,r,t,o,i){if(r===n.url.toString()){var s=new ErrorEvent("error",{message:e,filename:r,lineno:t,colno:o,error:i});n.dispatchEvent(s),n.onerror&&n.onerror(s)}},void 0!==r)throw new TypeError("Options are not supported for iframe workers");var o,i=new EventTarget;this.addEventListener=i.addEventListener.bind(i),this.removeEventListener=i.removeEventListener.bind(i),this.dispatchEvent=i.dispatchEvent.bind(i),document.body.appendChild(this.iframe=((o=document.createElement("iframe")).width=o.height=o.frameBorder="0",o)),this.worker.document.open(),this.worker.document.write("\n      <html>\n        <body>\n          <script>\n            postMessage = parent.postMessage.bind(parent)\n            importScripts = "+t.importScripts+'\n            addEventListener("error", function(ev) {\n              parent.dispatchEvent(new ErrorEvent("error", {\n                filename: "'+this.url+'",\n                error: ev.error\n              }))\n            })\n          <\/script>\n          <script src="'+e+'"><\/script>\n        </body>\n      </html>\n    '),this.worker.document.close(),window.addEventListener("message",this.handleMessage),window.onerror=this.handleError,this.ready=new Promise((function(e,r){n.worker.onload=e,n.worker.onerror=r}))}return e.prototype.terminate=function(){document.body.removeChild(this.iframe),window.removeEventListener("message",this.handleMessage),window.onerror=null},e.prototype.postMessage=function(e){var r=this;this.ready.catch().then((function(){r.worker.dispatchEvent(new MessageEvent("message",{data:e}))}))},Object.defineProperty(e.prototype,"worker",{get:function(){if(!this.iframe.contentWindow)throw new ReferenceError("Invalid iframe: expected window to be present");return this.iframe.contentWindow},enumerable:!1,configurable:!0}),e}();r.IFrameWorker=o},function(e,r,n){"use strict";Object.defineProperty(r,"__esModule",{value:!0}),r.importScripts=void 0,r.importScripts=function(){for(var e=[],r=0;r<arguments.length;r++)e[r]=arguments[r];return Promise.all(e.map((function(e){return new Promise((function(r){var n=document.createElement("script");n.src=e,n.addEventListener("load",(function(){return r})),document.body.appendChild(n)}))}))).then((function(){}))}}]));
