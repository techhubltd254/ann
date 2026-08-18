(function () {
  'use strict'

  if (typeof THREE === 'undefined' || !document.getElementById('kicc-hero-video')) return

  const video = document.getElementById('kicc-hero-video')
  video.pause()
  video.currentTime = 0

  const container = document.createElement('div')
  container.id = 'kicc-page-open-3d'
  Object.assign(container.style, {
    position: 'fixed', inset: '0', zIndex: '9999',
    pointerEvents: 'none', background: '#07090F',
  })
  document.body.appendChild(container)

  const scene = new THREE.Scene()
  const camera = new THREE.PerspectiveCamera(45, window.innerWidth / window.innerHeight, 0.1, 100)
  camera.position.set(0, 0, 6)

  const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true })
  renderer.setSize(window.innerWidth, window.innerHeight)
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2))
  container.appendChild(renderer.domElement)

  renderer.domElement.style.position = 'absolute'
  renderer.domElement.style.inset = '0'

  // Video plane — behind the pages
  const videoTex = new THREE.VideoTexture(video)
  videoTex.minFilter = THREE.LinearFilter
  videoTex.magFilter = THREE.LinearFilter
  videoTex.format = THREE.RGBAFormat

  const videoGeo = new THREE.PlaneGeometry(3.2, 1.8)
  const videoMat = new THREE.MeshBasicMaterial({
    map: videoTex,
    side: THREE.DoubleSide,
    transparent: true,
    opacity: 0,
  })
  const videoMesh = new THREE.Mesh(videoGeo, videoMat)
  videoMesh.position.z = -0.5
  scene.add(videoMesh)

  // Two page planes — form a book opening
  const pageMat = new THREE.MeshStandardMaterial({
    color: 0x1a1a2e,
    metalness: 0.3,
    roughness: 0.6,
    side: THREE.DoubleSide,
    transparent: true,
    opacity: 1,
  })
  const pageEdge = new THREE.MeshStandardMaterial({
    color: 0x901C1E,
    metalness: 0.2,
    roughness: 0.8,
    side: THREE.DoubleSide,
  })

  const pageGeo = new THREE.BoxGeometry(1.5, 2.2, 0.03)
  const leftPage = new THREE.Mesh(pageGeo, [pageMat, pageEdge, pageMat, pageMat, pageMat, pageMat])
  leftPage.position.set(-0.75, 0, 0)
  leftPage.rotation.y = 0

  const rightPage = new THREE.Mesh(pageGeo.clone(), [pageMat, pageEdge, pageMat, pageMat, pageMat, pageMat])
  rightPage.position.set(0.75, 0, 0)
  rightPage.rotation.y = 0

  // Group them so they rotate from the center spine
  const book = new THREE.Group()
  book.add(leftPage)
  book.add(rightPage)
  scene.add(book)

  // Lighting
  const ambient = new THREE.AmbientLight(0x404060, 1)
  scene.add(ambient)
  const directional = new THREE.DirectionalLight(0xffcd05, 0.5)
  directional.position.set(2, 3, 4)
  scene.add(directional)

  // Particles around the opening
  const particleCount = 300
  const particleGeo = new THREE.BufferGeometry()
  const positions = new Float32Array(particleCount * 3)
  for (let i = 0; i < particleCount * 3; i++) {
    positions[i] = (Math.random() - 0.5) * 8
  }
  particleGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3))
  const particleMat = new THREE.PointsMaterial({
    color: 0xffcd05,
    size: 0.02,
    transparent: true,
    opacity: 0.6,
    blending: THREE.AdditiveBlending,
  })
  const particles = new THREE.Points(particleGeo, particleMat)
  particles.position.z = -0.3
  scene.add(particles)

  // Animation
  const clock = new THREE.Clock()
  let progress = 0
  const duration = 2.5
  let holdTimer = 0

  video.play()

  function animate() {
    requestAnimationFrame(animate)
    const dt = clock.getDelta()
    progress = Math.min(progress + dt / duration, 1)

    // Ease-out cubic
    const ease = 1 - Math.pow(1 - progress, 3)

    // Pages open: left rotates -PI/2, right rotates PI/2
    leftPage.rotation.y = -ease * Math.PI * 0.45
    rightPage.rotation.y = ease * Math.PI * 0.45

    // Book group moves back slightly
    book.position.z = ease * 0.3

    // Video fade in
    videoMat.opacity = Math.min(1, Math.max(0, (progress - 0.3) * 2))

    // Pages fade out after video is visible
    pageMat.opacity = Math.max(0, 1 - (progress - 0.5) * 3)

    // Particles expand and fade
    particles.rotation.y += dt * 0.2
    particleMat.opacity = Math.max(0, 0.6 * (1 - progress * 0.8))

    // Camera subtle movement
    camera.position.y = Math.sin(progress * Math.PI) * 0.3

    renderer.render(scene, camera)

    // After animation completes, fade out the whole 3D scene
    if (progress >= 1) {
      holdTimer += dt
      const fadeOut = Math.min(1, (holdTimer - 0.5) / 0.8)
      if (fadeOut > 0) {
        container.style.opacity = Math.max(0, 1 - fadeOut)
        if (container.style.opacity === '0') {
          document.body.removeChild(container)
          renderer.dispose()
          return
        }
      }
    }
  }

  animate()

  // Resize handler
  window.addEventListener('resize', function () {
    camera.aspect = window.innerWidth / window.innerHeight
    camera.updateProjectionMatrix()
    renderer.setSize(window.innerWidth, window.innerHeight)
  })
})()