window.matchMedia||(window.matchMedia=function(){"use strict";var t,e,n,d=window.styleMedia||window.media;return d||(t=document.createElement("style"),e=document.getElementsByTagName("script")[0],n=null,t.type="text/css",t.id="matchmediajs-test",e?e.parentNode.insertBefore(t,e):document.head.appendChild(t),n="getComputedStyle"in window&&window.getComputedStyle(t,null)||t.currentStyle,d={matchMedium:function(e){e="@media "+e+"{ #matchmediajs-test { width: 1px; } }";return t.styleSheet?t.styleSheet.cssText=e:t.textContent=e,"1px"===n.width}}),function(e){return{matches:d.matchMedium(e||"all"),media:e||"all"}}}());
//# sourceMappingURL=maps/polyfill-matchMedia.min.js.map
