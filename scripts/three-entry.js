// Build entry — bundled by esbuild to produce assets/js/three.min.js.
// Sets window.THREE so neural-map.js can use the Three.js global.
var THREE = require( 'three' );
window.THREE = THREE;
