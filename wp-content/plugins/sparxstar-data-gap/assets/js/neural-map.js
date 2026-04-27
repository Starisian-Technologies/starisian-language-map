/**
 * Sparxstar Data Gap — Neural Language Map
 *
 * Self-contained Three.js globe visualization of world language families.
 * All geographic data is embedded (LAND_RINGS) — no external CDN fetches.
 *
 * @package sparxstar-data-gap
 * @version 1.0.0
 */

/* global THREE */
( function ( THREE ) {
	'use strict';

	// ─── Utility: lat/lon → 3-D point on unit sphere ─────────────────────────
	function latLonToVec3( lat, lon, radius ) {
		var phi   = ( 90 - lat ) * ( Math.PI / 180 );
		var theta = ( lon + 180 ) * ( Math.PI / 180 );
		return new THREE.Vector3(
			-radius * Math.sin( phi ) * Math.cos( theta ),
			 radius * Math.cos( phi ),
			 radius * Math.sin( phi ) * Math.sin( theta )
		);
	}

	// ─── Embedded land-ring data (replaces runtime GeoJSON / TopoJSON fetch) ──
	// Each entry is an array of [longitude, latitude] coordinate pairs forming a
	// closed polygon ring.  Simplified Natural Earth 110m land outlines, public
	// domain (naturalearthdata.com).
	var LAND_RINGS = [
		// ── North America ─────────────────────────────────────────────────────
		[
			[-168,71],[-163,71],[-156,72],[-141,60],[-137,59],[-130,55],[-125,50],
			[-124,47],[-123,38],[-118,34],[-110,23],[-105,20],[-92,16],[-83,10],
			[-77,8], [-76,9], [-83,10],[-84,15],[-90,15],[-92,16],[-97,19],
			[-105,20],[-110,23],[-118,34],[-123,38],[-124,47],[-125,50],
			[-130,55],[-137,59],[-141,60],[-156,72],[-163,71],[-168,71],
			[-168,60],[-161,58],[-163,55],[-165,60],[-168,60],[-171,63],
			[-168,71]
		],
		[
			[-52,47],[-53,47],[-56,47],[-59,46],[-64,44],[-66,44],[-67,45],
			[-66,44],[-64,44],[-59,46],[-56,47],[-53,47],[-52,47]
		],
		// ── Greenland ─────────────────────────────────────────────────────────
		[
			[-73,83],[-26,83],[-18,75],[-22,70],[-26,65],[-43,60],[-52,65],
			[-57,75],[-60,80],[-73,83]
		],
		// ── South America ─────────────────────────────────────────────────────
		[
			[-82,8], [-77,8], [-76,2], [-72,-5],[-70,-10],[-75,-15],[-72,-30],
			[-68,-55],[-65,-55],[-58,-51],[-53,-34],[-48,-28],[-44,-23],[-37,-11],
			[-35,-5], [-38,0], [-50,2], [-60,4], [-68,6], [-78,8], [-82,8]
		],
		// ── Europe ────────────────────────────────────────────────────────────
		[
			[-10,36],[-9,38],[-9,42],[-6,44],[-3,44],[0,43],[3,44],[5,43],
			[8,44],[10,44],[12,45],[14,45],[15,47],[17,48],[19,49],[23,54],
			[26,58],[28,60],[30,60],[27,56],[24,56],[20,57],[17,58],[15,57],
			[12,56],[8,55],[5,52],[3,51],[2,51],[0,50],[-2,49],[-5,48],
			[-7,44],[-9,38],[-10,36]
		],
		// Norway / Scandinavia simplified
		[
			[5,58],[8,57],[11,56],[14,55],[18,59],[24,64],[28,70],[25,71],
			[20,70],[16,69],[12,64],[8,61],[5,58]
		],
		// ── Africa ────────────────────────────────────────────────────────────
		[
			[-17,15],[-16,20],[-13,27],[-5,35],[0,37],[10,37],[12,37],[16,33],
			[25,37],[34,30],[37,22],[42,12],[44,11],[44,4],[40,-5],[38,-15],
			[35,-25],[27,-35],[18,-35],[15,-29],[12,-20],[10,-5],[2,5],
			[0,5],[-2,5],[-5,5],[-10,5],[-15,11],[-17,15]
		],
		// Madagascar
		[
			[44,-12],[47,-13],[50,-16],[50,-22],[47,-25],[44,-22],[43,-18],[44,-12]
		],
		// ── Asia (mainland) ───────────────────────────────────────────────────
		[
			[26,42],[34,37],[36,36],[38,37],[42,37],[48,38],[53,37],[60,37],
			[65,38],[70,37],[75,37],[80,32],[85,27],[90,22],[96,22],[100,20],
			[103,2],[108,2],[115,5],[120,10],[125,15],[130,20],[135,35],
			[140,40],[143,43],[142,53],[136,55],[130,65],[120,72],[115,74],
			[100,72],[90,68],[80,72],[70,68],[60,68],[50,65],[42,58],[40,55],
			[36,45],[34,45],[30,45],[28,44],[26,42]
		],
		// Indian subcontinent
		[
			[66,24],[68,22],[70,20],[72,18],[72,16],[76,8],[78,8],[80,10],
			[80,14],[82,18],[84,22],[86,22],[88,26],[90,26],[92,26],[95,28],
			[96,28],[96,26],[92,22],[88,22],[86,18],[82,14],[80,10],[78,8],
			[76,8],[72,16],[70,20],[68,22],[66,24]
		],
		// Japan
		[
			[130,31],[131,32],[132,34],[133,35],[135,35],[137,37],[138,37],
			[140,39],[141,41],[143,44],[141,43],[139,38],[137,37],[135,35],
			[133,34],[131,32],[130,31]
		],
		// Indonesia / SE Asia islands (simplified)
		[
			[95,5],[100,5],[105,5],[110,5],[115,2],[120,-2],[125,-5],[130,-8],
			[134,-4],[130,-5],[125,-5],[120,-2],[115,-2],[110,0],[105,0],
			[100,0],[96,2],[95,5]
		],
		// ── Australia ─────────────────────────────────────────────────────────
		[
			[114,-22],[116,-20],[118,-18],[122,-15],[130,-12],[136,-12],[138,-14],
			[140,-16],[142,-18],[144,-18],[148,-20],[152,-24],[153,-27],[152,-29],
			[150,-36],[148,-38],[145,-38],[140,-36],[136,-34],[132,-32],[126,-34],
			[122,-34],[116,-34],[114,-32],[114,-22]
		],
		// New Zealand (N Island)
		[
			[172,-34],[174,-35],[176,-37],[176,-38],[174,-39],[172,-38],[172,-36],
			[172,-34]
		],
		// ── Antarctica (simplified arc) ───────────────────────────────────────
		[
			[-180,-70],[-90,-70],[0,-70],[90,-70],[180,-70],[180,-90],
			[0,-90],[-180,-90],[-180,-70]
		]
	];

	// ─── Language-family data ─────────────────────────────────────────────────
	// lat, lon: centroid of the family's primary geographic spread.
	var LANGUAGE_FAMILIES = [
		{ id:  0, name: 'Indo-European',      lat:  50, lon:  10, color: 0x4488ff, speakers: 3200 },
		{ id:  1, name: 'Sino-Tibetan',       lat:  30, lon: 104, color: 0xff8844, speakers: 1400 },
		{ id:  2, name: 'Niger-Congo',        lat:   5, lon:  20, color: 0x44ff88, speakers:  730 },
		{ id:  3, name: 'Afro-Asiatic',       lat:  20, lon:  30, color: 0xffcc44, speakers:  500 },
		{ id:  4, name: 'Austronesian',       lat:   0, lon: 120, color: 0xff44cc, speakers:  386 },
		{ id:  5, name: 'Dravidian',          lat:  14, lon:  78, color: 0x44ccff, speakers:  230 },
		{ id:  6, name: 'Turkic',             lat:  42, lon:  60, color: 0xcc44ff, speakers:  200 },
		{ id:  7, name: 'Japonic',            lat:  35, lon: 137, color: 0xff4444, speakers:  128 },
		{ id:  8, name: 'Koreanic',           lat:  37, lon: 127, color: 0x44ffcc, speakers:   80 },
		{ id:  9, name: 'Uralic',             lat:  62, lon:  25, color: 0xaaffaa, speakers:   25 },
		{ id: 10, name: 'Tai-Kadai',          lat:  20, lon: 100, color: 0xffaaaa, speakers:   93 },
		{ id: 11, name: 'Austro-Asiatic',     lat:  15, lon:  98, color: 0xaaaaff, speakers:  117 },
		{ id: 12, name: 'Nilo-Saharan',       lat:   8, lon:  30, color: 0xffff44, speakers:   50 },
		{ id: 13, name: 'Trans-New Guinea',   lat:  -6, lon: 143, color: 0xff8800, speakers:    4 },
		{ id: 14, name: 'Na-Dene',            lat:  60, lon:-130, color: 0x88ff44, speakers:    2 },
		{ id: 15, name: 'Algic',              lat:  45, lon: -90, color: 0x8844ff, speakers:    1 },
		{ id: 16, name: 'Quechuan',           lat: -13, lon: -72, color: 0xff6688, speakers:   10 },
		{ id: 17, name: 'Tupian',             lat: -10, lon: -55, color: 0x66ff88, speakers:    5 },
		{ id: 18, name: 'Nakh-Daghestanian', lat:  43, lon:  45, color: 0xffcc88, speakers:    2 },
		{ id: 19, name: 'Khoisan',            lat: -22, lon:  22, color: 0xff88ff, speakers:    1 }
	];

	// Neural connections (indexes into LANGUAGE_FAMILIES indicating contact /
	// typological similarity or historical relationship).
	var NEURAL_EDGES = [
		[ 0,  9],  // Indo-European ↔ Uralic (Sprachbund, geographic proximity)
		[ 0, 18],  // Indo-European ↔ Nakh-Daghestanian
		[ 0,  6],  // Indo-European ↔ Turkic
		[ 1, 10],  // Sino-Tibetan ↔ Tai-Kadai
		[ 1, 11],  // Sino-Tibetan ↔ Austro-Asiatic
		[ 1,  8],  // Sino-Tibetan ↔ Koreanic
		[ 1,  7],  // Sino-Tibetan ↔ Japonic
		[ 8,  7],  // Koreanic ↔ Japonic
		[ 3,  2],  // Afro-Asiatic ↔ Niger-Congo
		[ 3, 12],  // Afro-Asiatic ↔ Nilo-Saharan
		[ 2, 12],  // Niger-Congo ↔ Nilo-Saharan
		[ 2, 19],  // Niger-Congo ↔ Khoisan
		[ 4, 13],  // Austronesian ↔ Trans-New Guinea
		[ 5, 11],  // Dravidian ↔ Austro-Asiatic
		[ 6,  0],  // Turkic ↔ Indo-European (already above, kept for weight)
		[14, 15],  // Na-Dene ↔ Algic
		[16, 17],  // Quechuan ↔ Tupian
		[ 4,  5],  // Austronesian ↔ Dravidian (maritime contact)
		[10, 11],  // Tai-Kadai ↔ Austro-Asiatic
	];

	// ─── Initialise a single map instance ───────────────────
	function init( container ) {
		if ( ! container ) {
			return;
		}

		// ── WebGL availability check ─────────────────────────────────────────
		if ( ! window.WebGLRenderingContext ) {
			container.innerHTML = '<p class="spx-neural-map-no-webgl">WebGL is not supported by your browser.</p>';
			return;
		}

		// ── Scene setup ──────────────────────────────────────────────────────
		var W = container.clientWidth  || 800;
		var H = container.clientHeight || 600;
		var pixelRatio = Math.min( window.devicePixelRatio || 1, 2 );

		var renderer;
		try {
			renderer = new THREE.WebGLRenderer( { antialias: true, alpha: true } );
		} catch ( e ) {
			container.innerHTML = '<p class="spx-neural-map-no-webgl">WebGL could not be initialised on this device.</p>';
			return;
		}
		renderer.setPixelRatio( pixelRatio );
		renderer.setSize( W, H );
		renderer.setClearColor( 0x000000, 1 );
		container.appendChild( renderer.domElement );

		var scene  = new THREE.Scene();
		var camera = new THREE.PerspectiveCamera( 45, W / H, 0.1, 1000 );
		camera.position.set( 0, 0, 4.5 );

		// ── Lighting ─────────────────────────────────────────────────────────
		scene.add( new THREE.AmbientLight( 0x111133, 1.5 ) );
		var dirLight = new THREE.DirectionalLight( 0x4466ff, 2 );
		dirLight.position.set( 5, 3, 5 );
		scene.add( dirLight );

		// ── Stars background ─────────────────────────────────────────────────
		( function addStars() {
			var geo   = new THREE.BufferGeometry();
			var count = 4000;
			var pos   = new Float32Array( count * 3 );
			for ( var i = 0; i < count * 3; i++ ) {
				pos[ i ] = ( Math.random() - 0.5 ) * 400;
			}
			geo.setAttribute( 'position', new THREE.BufferAttribute( pos, 3 ) );
			var mat = new THREE.PointsMaterial( { color: 0xffffff, size: 0.15, sizeAttenuation: true } );
			scene.add( new THREE.Points( geo, mat ) );
		}() );

		// ── Globe sphere ─────────────────────────────────────────────────────
		var GLOBE_RADIUS = 1.5;
		var sphereGeo = new THREE.SphereGeometry( GLOBE_RADIUS * 0.998, 64, 64 );
		var sphereMat = new THREE.MeshPhongMaterial( {
			color:       0x050a1a,
			emissive:    0x050a1a,
			shininess:   60,
			transparent: true,
			opacity:     0.92
		} );
		scene.add( new THREE.Mesh( sphereGeo, sphereMat ) );

		// Outer glow ring
		var glowGeo = new THREE.SphereGeometry( GLOBE_RADIUS * 1.04, 32, 32 );
		var glowMat = new THREE.MeshBasicMaterial( {
			color:       0x1a3a8f,
			transparent: true,
			opacity:     0.08,
			side:        THREE.BackSide
		} );
		scene.add( new THREE.Mesh( glowGeo, glowMat ) );

		// ── Land rings ───────────────────────────────────────────────────────
		var landMat = new THREE.LineBasicMaterial( { color: 0x1a6fff, linewidth: 1, opacity: 0.7, transparent: true } );

		LAND_RINGS.forEach( function ( ring ) {
			var points = [];
			for ( var i = 0; i < ring.length; i++ ) {
				points.push( latLonToVec3( ring[ i ][ 1 ], ring[ i ][ 0 ], GLOBE_RADIUS * 1.001 ) );
			}
			// Close ring
			if ( points.length > 1 ) {
				points.push( points[ 0 ].clone() );
			}
			var geo = new THREE.BufferGeometry().setFromPoints( points );
			scene.add( new THREE.Line( geo, landMat ) );
		} );

		// ── Neural connection arcs ────────────────────────────────────────────
		var arcMat = new THREE.LineBasicMaterial( {
			color: 0x2255aa, opacity: 0.35, transparent: true, linewidth: 1
		} );

		NEURAL_EDGES.forEach( function ( edge ) {
			var a = LANGUAGE_FAMILIES[ edge[ 0 ] ];
			var b = LANGUAGE_FAMILIES[ edge[ 1 ] ];
			if ( ! a || ! b ) { return; }

			var pA = latLonToVec3( a.lat, a.lon, GLOBE_RADIUS * 1.003 );
			var pB = latLonToVec3( b.lat, b.lon, GLOBE_RADIUS * 1.003 );
			var mid = pA.clone().add( pB ).multiplyScalar( 0.5 );
			var lift = ( 1 + pA.distanceTo( pB ) * 0.3 );
			mid.normalize().multiplyScalar( GLOBE_RADIUS * lift );

			var curve  = new THREE.QuadraticBezierCurve3( pA, mid, pB );
			var pts    = curve.getPoints( 40 );
			var arcGeo = new THREE.BufferGeometry().setFromPoints( pts );
			scene.add( new THREE.Line( arcGeo, arcMat ) );
		} );

		// ── Language-node particles ───────────────────────────────────────────
		var nodeObjects = [];
		LANGUAGE_FAMILIES.forEach( function ( fam ) {
			var pos = latLonToVec3( fam.lat, fam.lon, GLOBE_RADIUS * 1.006 );

			// Pulse ring (flat torus)
			var ringGeo = new THREE.TorusGeometry( 0.025, 0.006, 6, 24 );
			var ringMat = new THREE.MeshBasicMaterial( { color: fam.color } );
			var ringMesh = new THREE.Mesh( ringGeo, ringMat );
			ringMesh.position.copy( pos );
			ringMesh.lookAt( new THREE.Vector3( 0, 0, 0 ) );
			scene.add( ringMesh );

			// Core dot
			var dotGeo = new THREE.SphereGeometry( 0.018, 8, 8 );
			var dotMat = new THREE.MeshBasicMaterial( { color: fam.color } );
			var dotMesh = new THREE.Mesh( dotGeo, dotMat );
			dotMesh.position.copy( pos );
			scene.add( dotMesh );

			nodeObjects.push( { ring: ringMesh, dot: dotMesh, phase: Math.random() * Math.PI * 2 } );
		} );

		// ── Groups for rotation ───────────────────────────────────────────────
		// Move all non-light objects into a pivot group so the entire globe
		// (land rings, nodes, arcs) rotates together while lights stay fixed.
		var pivot = new THREE.Group();
		scene.children.slice().forEach( function ( obj ) {
			if ( obj.type !== 'AmbientLight' && obj.type !== 'DirectionalLight' ) {
				pivot.add( obj );
			}
		} );
		scene.add( pivot );

		// ── Pointer drag interaction (unified mouse + touch + stylus) ────────
		var isDragging    = false;
		var prevMouseX    = 0;
		var prevMouseY    = 0;
		var rotationSpeed = { x: 0, y: 0.0015 };

		function onPointerDown( e ) {
			isDragging = true;
			prevMouseX = e.clientX;
			prevMouseY = e.clientY;
			renderer.domElement.setPointerCapture( e.pointerId );
		}
		function onPointerMove( e ) {
			if ( ! isDragging ) { return; }
			var dx = ( e.clientX - prevMouseX ) * 0.005;
			var dy = ( e.clientY - prevMouseY ) * 0.005;
			pivot.rotation.y += dx;
			pivot.rotation.x += dy;
			// Clamp vertical rotation
			pivot.rotation.x = Math.max( -1.2, Math.min( 1.2, pivot.rotation.x ) );
			prevMouseX = e.clientX;
			prevMouseY = e.clientY;
			rotationSpeed.y = dx * 0.1;
		}
		function onPointerUp( e ) {
			isDragging = false;
			if ( renderer.domElement.hasPointerCapture( e.pointerId ) ) {
				renderer.domElement.releasePointerCapture( e.pointerId );
			}
		}

		renderer.domElement.addEventListener( 'pointerdown', onPointerDown );   // not passive — needs setPointerCapture
		renderer.domElement.addEventListener( 'pointermove', onPointerMove, { passive: true } );
		renderer.domElement.addEventListener( 'pointerup',   onPointerUp,   { passive: true } );
		renderer.domElement.addEventListener( 'pointercancel', onPointerUp, { passive: true } );

		// ── Tooltip on click ─────────────────────────────────────────────────
		var raycaster = new THREE.Raycaster();
		var mouse     = new THREE.Vector2();
		var tooltip   = document.getElementById( container.getAttribute( 'data-tooltip-id' ) || '' );
		var dotMeshes = nodeObjects.map( function ( n ) { return n.dot; } );
		var dotMeshToFamilyIndex = new WeakMap();

		dotMeshes.forEach( function ( dotMesh, index ) {
			dotMeshToFamilyIndex.set( dotMesh, index );
		} );

		function onTooltipClick( e ) {
			var rect = renderer.domElement.getBoundingClientRect();
			mouse.x  = ( ( e.clientX - rect.left ) / rect.width  ) * 2 - 1;
			mouse.y  = -( ( e.clientY - rect.top  ) / rect.height ) * 2 + 1;
			raycaster.setFromCamera( mouse, camera );

			var hits = raycaster.intersectObjects( dotMeshes );
			if ( hits.length > 0 ) {
				var idx = dotMeshToFamilyIndex.get( hits[ 0 ].object );
				if ( 'number' === typeof idx ) {
					var fam = LANGUAGE_FAMILIES[ idx ];
					if ( fam ) {
						tooltip.textContent = fam.name + ' — ~' + fam.speakers + 'M speakers';
						tooltip.style.display  = 'block';
						tooltip.style.left     = e.clientX + 'px';
						tooltip.style.top      = ( e.clientY - 36 ) + 'px';
					}
				}
			} else {
				tooltip.style.display = 'none';
			}
		}

		if ( tooltip ) {
			renderer.domElement.addEventListener( 'click', onTooltipClick, { passive: true } );
		}

		// ── Resize handler (ResizeObserver, per-container) ───────────────────
		var resizeObserver = null;
		var onResizeFallback = null;
		if ( typeof ResizeObserver !== 'undefined' ) {
			resizeObserver = new ResizeObserver( function ( entries ) {
				var rect = entries[ 0 ].contentRect;
				if ( ! rect.width || ! rect.height ) { return; }
				camera.aspect = rect.width / rect.height;
				camera.updateProjectionMatrix();
				renderer.setSize( rect.width, rect.height );
			} );
			resizeObserver.observe( container );
		} else {
			// Fallback: global resize event for older browsers.
			onResizeFallback = function () {
				var w = container.clientWidth;
				var h = container.clientHeight;
				if ( ! w || ! h ) { return; }
				camera.aspect = w / h;
				camera.updateProjectionMatrix();
				renderer.setSize( w, h );
			};
			window.addEventListener( 'resize', onResizeFallback, { passive: true } );
		}

		// ── Animation loop ────────────────────────────────────────────────────
		var clock = new THREE.Clock();
		var animFrameId;
		function animate() {
			animFrameId = requestAnimationFrame( animate );

			var elapsed = clock.getElapsedTime();

			// Slow auto-rotation
			if ( ! isDragging ) {
				pivot.rotation.y += rotationSpeed.y;
				rotationSpeed.y  *= 0.98; // inertia decay
				if ( Math.abs( rotationSpeed.y ) < 0.0015 ) {
					rotationSpeed.y = 0.0015;
				}
			}

			// Pulse language nodes
			nodeObjects.forEach( function ( n, i ) {
				var scale = 1 + 0.25 * Math.sin( elapsed * 2 + n.phase );
				n.ring.scale.set( scale, scale, scale );
			} );

			renderer.render( scene, camera );
		}
		animate();

		// ── Teardown hook ─────────────────────────────────────────────────────
		// Attach a spxDestroy() method to the container so callers (e.g. page
		// builders, SPAs) can clean up GPU resources and observers.
		container.spxDestroy = function () {
			cancelAnimationFrame( animFrameId );
			renderer.domElement.removeEventListener( 'pointerdown',   onPointerDown );
			renderer.domElement.removeEventListener( 'pointermove',   onPointerMove );
			renderer.domElement.removeEventListener( 'pointerup',     onPointerUp );
			renderer.domElement.removeEventListener( 'pointercancel', onPointerUp );
			if ( tooltip ) {
				renderer.domElement.removeEventListener( 'click', onTooltipClick );
			}
			if ( resizeObserver ) {
				resizeObserver.disconnect();
			}
			if ( onResizeFallback ) {
				window.removeEventListener( 'resize', onResizeFallback );
			}
			// Dispose all geometries and materials in the scene graph to free GPU
			// memory.  This is critical when maps are created/destroyed repeatedly
			// (SPA navigation, page builders) and prevents WebGL context exhaustion.
			scene.traverse( function ( obj ) {
				if ( obj.geometry ) {
					obj.geometry.dispose();
				}
				if ( obj.material ) {
					if ( Array.isArray( obj.material ) ) {
						obj.material.forEach( function ( mat ) { mat.dispose(); } );
					} else {
						obj.material.dispose();
					}
				}
			} );
			renderer.dispose();
			if ( renderer.domElement.parentNode ) {
				renderer.domElement.parentNode.removeChild( renderer.domElement );
			}
		};
	}

	// ─── Bootstrap: lazy-init each map when it enters the viewport ────────
	// Creating a WebGL renderer is expensive; deferring until the container
	// is actually visible avoids wasted GPU work on long pages where the
	// shortcode is below the fold.
	function bootAll() {
		var containers = document.querySelectorAll( '.spx-neural-map-canvas' );

		// Fall back to eager init when IntersectionObserver is unavailable
		// (e.g. very old Android WebView).
		if ( typeof IntersectionObserver === 'undefined' ) {
			for ( var i = 0; i < containers.length; i++ ) {
				init( containers[ i ] );
			}
			return;
		}

		var remaining = containers.length;
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					// Stop observing this element and initialise the map.
					observer.unobserve( entry.target );
					init( entry.target );
					remaining--;

					if ( remaining === 0 ) {
						observer.disconnect();
					}
				} );
			},
			{ threshold: 0.1 }   // trigger when 10 % of the canvas is visible
		);

		for ( var i = 0; i < containers.length; i++ ) {
			observer.observe( containers[ i ] );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', bootAll );
	} else {
		bootAll();
	}

}( THREE ) );
