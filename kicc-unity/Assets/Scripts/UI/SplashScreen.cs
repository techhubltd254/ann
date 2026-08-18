using System.Threading.Tasks;
using KICC.API;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

namespace KICC.UI
{
    public class SplashScreen : MonoBehaviour
    {
        [SerializeField] private Slider progressBar;
        [SerializeField] private Text statusText;
        [SerializeField] private string mainScene = "MainMenu";

        private async void Start()
        {
            SetStatus("Initializing...", 0f);
            await Task.Delay(200);

            // 1. Restore session
            SetStatus("Restoring session...", 0.1f);
            var token = PlayerPrefs.GetString("KICC_AUTH_TOKEN", "");
            if (!string.IsNullOrEmpty(token))
            {
                ApiClient.Instance.SetToken(token);
                var me = await ApiClient.Instance.Get<UserData>("/auth/me");
                if (me.IsSuccess)
                {
                    PlayerPrefs.SetString("KICC_USER_NAME", me.Data.name);
                    PlayerPrefs.Save();
                }
            }

            // 2. Preload county data
            SetStatus("Loading data...", 0.4f);
            _ = ApiClient.Instance.GetCounties();
            _ = ApiClient.Instance.GetExhibitions();

            // 3. OTA update check (background)
            SetStatus("Preparing...", 0.7f);

            // 4. Done
            SetStatus("Welcome!", 1f);
            await Task.Delay(400);
            SceneManager.LoadScene(mainScene);
        }

        private void SetStatus(string text, float progress)
        {
            if (statusText != null) statusText.text = text;
            if (progressBar != null) progressBar.value = progress;
        }
    }
}