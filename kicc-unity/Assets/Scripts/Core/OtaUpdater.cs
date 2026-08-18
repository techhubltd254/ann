using System;
using System.Collections;
using System.IO;
using System.Net.Http;
using UnityEngine;
using UnityEngine.Networking;

namespace KICC.Core
{
    /// <summary>
    /// Over-the-Air update system. Downloads and applies updates from the
    /// KICC CDN. Supports Addressable asset bundles for scalable content
    /// delivery (county 3D models, 4K textures, video tours).
    ///
    /// Update manifest URL: https://kicctest.org/api/updates/manifest
    /// Asset bundles:       https://kicc-r2-media.techhubltd254.workers.dev/unity/
    /// </summary>
    public class OtaUpdater : MonoBehaviour
    {
        [SerializeField] private string manifestUrl = "https://kicctest.org/api/updates/manifest";
        [SerializeField] private string bundleCdn = "https://kicc-r2-media.techhubltd254.workers.dev/unity/";
        [SerializeField] private int currentVersion = 1;

        public event Action<float> OnProgress;
        public event Action<string> OnError;
        public event Action<bool> OnComplete; // true = update applied, false = up to date

        private void Start()
        {
            StartCoroutine(CheckForUpdates());
        }

        private IEnumerator CheckForUpdates()
        {
            using var www = UnityWebRequest.Get(manifestUrl);
            www.timeout = 15;
            yield return www.SendWebRequest();

            if (www.result != UnityWebRequest.Result.Success)
            {
                Debug.Log("[KICC OTA] Update manifest unavailable (no internet or first launch)");
                OnComplete?.Invoke(false);
                yield break;
            }

            var manifest = www.downloadHandler.text;
            var latestVersion = ParseVersion(manifest);

            if (latestVersion <= currentVersion)
            {
                Debug.Log($"[KICC OTA] Up to date (v{currentVersion})");
                OnComplete?.Invoke(false);
                yield break;
            }

            Debug.Log($"[KICC OTA] Update available: v{currentVersion} -> v{latestVersion}");
            yield return DownloadAndApply(manifest);
        }

        private IEnumerator DownloadAndApply(string manifest)
        {
            var bundles = ParseBundles(manifest);
            var total = bundles.Length;
            var completed = 0;

            foreach (var bundle in bundles)
            {
                var url = $"{bundleCdn}{bundle}";
                using var www = UnityWebRequestAssetBundle.GetAssetBundle(url);
                www.SendWebRequest();

                while (!www.isDone)
                {
                    OnProgress?.Invoke((completed + www.downloadProgress) / total);
                    yield return null;
                }

                if (www.result == UnityWebRequest.Result.Success)
                {
                    var assetBundle = DownloadHandlerAssetBundle.GetContent(www);
                    if (assetBundle != null)
                    {
                        // Assets are automatically cached by Unity's asset bundle system
                        assetBundle.Unload(false);
                    }
                }
                else
                {
                    OnError?.Invoke($"Failed to download {bundle}: {www.error}");
                }

                completed++;
                OnProgress?.Invoke((float)completed / total);
            }

            PlayerPrefs.SetInt("KICC_APP_VERSION", ParseVersion(manifest));
            PlayerPrefs.Save();
            Debug.Log("[KICC OTA] Update applied successfully");
            OnComplete?.Invoke(true);
        }

        private int ParseVersion(string manifest)
        {
            try
            {
                var data = JsonUtility.FromJson<UpdateManifest>(manifest);
                return data.version;
            }
            catch
            {
                return currentVersion;
            }
        }

        private string[] ParseBundles(string manifest)
        {
            try
            {
                var data = JsonUtility.FromJson<UpdateManifest>(manifest);
                return data.bundles ?? Array.Empty<string>();
            }
            catch
            {
                return Array.Empty<string>();
            }
        }

        [Serializable]
        private class UpdateManifest
        {
            public int version;
            public string[] bundles;
            public string[] scenes;
        }
    }
}