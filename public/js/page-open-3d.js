(async function () {
  'use strict'

  // Three.js overlay on top of the video — no video element manipulation
  var THREE
  try {
    THREE = (await import('three')).default || await import('three')
  } catch (e) {
    return
  }

  var container = document.createElement('div')
  container.id = 'kicc-page-open-3d'
  container.style.cssText = 'position:fixed;inset:0;z-index:9999;pointer-events:none'
  document.body.appendChild(container)

  var scene = new THREE.Scene()
  var camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 100)
  camera.position.set(0, 0, 6)

  var renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true })
  renderer.setSize(window.innerWidth, window.innerHeight)
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  container.appendChild(renderer.domElement)
  renderer.domElement.style.cssText = 'position:absolute;inset:0'

  // Dark background that fades out — reveals the video underneath
  var bgMat = new THREE.MeshBasicMaterial({ color: 0x07090F, transparent: true, opacity: 1, side: THREE.DoubleSide })
  var bgMesh = new THREE.Mesh(new THREE.PlaneGeometry(10, 10), bgMat)
  bgMesh.position.z = -2
  scene.add(bgMesh)

  // Two page planes — book opening effect
  var pageMat = new THREE.MeshStandardMaterial({
    color: 0x1a1a2e, metalness: 0.3, roughness: 0.6, side: THREE.DoubleSide, transparent: true, opacity: 1
  })
  var pageEdge = new THREE.MeshStandardMaterial({
    color: 0x901C1E, metalness: 0.2, roughness: 0.8, side: THREE.DoubleSide
  })
  var pageGeo = new THREE.BoxGeometry(1.5, 2.2, 0.03)
  var leftPage = new THREE.Mesh(pageGeo, [pageMat, pageEdge, pageMat, pageMat, pageMat, pageMat])
  leftPage.position.set(-0.75, 0, 0)
  var rightPage = new THREE.Mesh(pageGeo.clone(), [pageMat, pageEdge, pageMat, pageMat, pageMat, pageMat])
  rightPage.position.set(0.75, 0, 0)

  var book = new THREE.Group()
  book.add(leftPage, rightPage)
  scene.add(book)

  // Lighting
  scene.add(new THREE.AmbientLight(0x404060, 1))
  var light = new THREE.DirectionalLight(0xffcd05, 0.5)
  light.position.set(2, 3, 4)
  scene.add(light)

  // Particles
  var pCount = 300
  var pGeo = new THREE.BufferGeometry()
  var pos = new Float32Array(pCount * 3)
  for (var i = 0; i < pCount * 3; i++) pos[i] = (Math.random() - 0.5) * 8
  pGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3))
  var pMat = new THREE.PointsMaterial({
    color: 0xffcd05, size: 0.02, transparent: true, opacity: 0.6, blending: THREE.AdditiveBlending
  })
  var particles = new THREE.Points(pGeo, pMat)
  particles.position.z = -0.3
  scene.add(particles)

  // Animation
  var clock = new THREE.Clock()
  var progress = 0
  var holdTimer = 0

  function animate() {
    requestAnimationFrame(animate)
    var dt = clock.getDelta()
    progress = Math.min(progress + dt / 2.5, 1)
    var ease = 1 - Math.pow(1 - progress, 3)

    leftPage.rotation.y = -ease * Math.PI * 0.45
    rightPage.rotation.y = ease * Math.PI * 0.45
    book.position.z = ease * 0.3
    pageMat.opacity = Math.max(0, 1 - (progress - 0.3) * 3)
    pMat.opacity = Math.max(0, 0.6 * (1 - progress * 0.8))
    particles.rotation.y += dt * 0.2
    camera.position.y = Math.sin(progress * Math.PI) * 0.3
    bgMat.opacity = Math.max(0, 1 - progress * 1.2)

    renderer.render(scene, camera)

    if (progress >= 1) {
      holdTimer += dt
      if (holdTimer >= 0.5) {
        var fade = Math.min(1, (holdTimer - 0.5) / 0.8)
        container.style.opacity = Math.max(0, 1 - fade)
        if (container.style.opacity === '0') {
          document.body.removeChild(container)
          renderer.dispose()
          return
        }
      }
    }
  }

  animate()

  window.addEventListener('resize', function () {
    camera.aspect = window.innerWidth / window.innerHeight
    camera.updateProjectionMatrix()
    renderer.setSize(window.innerWidth, window.innerHeight)
  })
})()