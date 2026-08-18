using UnityEngine;
using UnityEngine.Networking;

namespace KICC.XR
{
    /// <summary>
    /// 360° spherical panorama viewer for county immersive experiences.
    /// Loads 8K equirectangular images from the KICC CDN.
    /// Supports gyroscope look (mobile) and touch-drag (desktop).
    /// </summary>
    [RequireComponent(typeof(Renderer))]
    public class PanoramaViewer : MonoBehaviour
    {
        [SerializeField] private string baseUrl = "https://kicc-r2-media.techhubltd254.workers.dev/storage/360/";
        [SerializeField] private float rotationSpeed = 2f;
        [SerializeField] private bool useGyro = true;

        private Renderer _renderer;
        private Material _material;
        private Gyroscope _gyro;
        private Quaternion _baseRotation;
        private float _rotationX;
        private float _rotationY;
        private bool _hasGyro;

        private void Awake()
        {
            _renderer = GetComponent<Renderer>();
            _material = _renderer.material;
            _hasGyro = useGyro && SystemInfo.supportsGyroscope;
            if (_hasGyro)
            {
                _gyro = Input.gyro;
                _gyro.enabled = true;
                _baseRotation = transform.rotation;
            }
        }

        public async void LoadPanorama(string countySlug)
        {
            var url = $"{baseUrl}{countySlug}/panorama.jpg";
            var tex = new Texture2D(2, 2);

            using var www = UnityWebRequestTexture.GetTexture(url);
            var operation = www.SendWebRequest();

            while (!operation.isDone)
                await System.Threading.Tasks.Task.Yield();

            if (www.result == UnityWebRequest.Result.Success)
            {
                var downloaded = DownloadHandlerTexture.GetContent(www);
                if (_material != null)
                    _material.mainTexture = downloaded;
            }
        }

        private void Update()
        {
            if (_hasGyro && _gyro != null)
            {
                transform.rotation = _baseRotation * _gyro.attitude * new Quaternion(0, 0, 1, 0);
            }
            else if (Input.touchCount > 0)
            {
                var touch = Input.GetTouch(0);
                if (touch.phase == TouchPhase.Moved)
                {
                    _rotationX -= touch.deltaPosition.x * rotationSpeed * 0.01f;
                    _rotationY -= touch.deltaPosition.y * rotationSpeed * 0.01f;
                    _rotationY = Mathf.Clamp(_rotationY, -90f, 90f);
                    transform.localRotation = Quaternion.Euler(_rotationY, _rotationX, 0);
                }
            }
        }

        private void OnDestroy()
        {
            if (_material != null)
                Destroy(_material);
        }
    }
}