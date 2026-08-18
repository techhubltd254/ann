using KICC.API;
using UnityEngine;
using UnityEngine.SceneManagement;
using UnityEngine.UI;

namespace KICC.UI
{
    public class MainMenu : MonoBehaviour
    {
        [SerializeField] private Transform countyListParent;
        [SerializeField] private GameObject countyCardPrefab;
        [SerializeField] private Text welcomeText;

        private async void Start()
        {
            var name = PlayerPrefs.GetString("KICC_USER_NAME", "Explorer");
            if (welcomeText != null)
                welcomeText.text = $"Welcome, {name}!";

            LoadCounties();
        }

        private async void LoadCounties()
        {
            var response = await ApiClient.Instance.GetCounties();
            if (!response.IsSuccess || countyListParent == null) return;

            var counties = JsonHelper.FromJson<CountyData>(response.Raw);
            if (counties == null) return;

            foreach (var county in counties)
            {
                if (countyListParent == null) break;
                var card = Instantiate(countyCardPrefab, countyListParent);
                var cardScript = card.GetComponent<CountyCard>();
                if (cardScript != null)
                    cardScript.Setup(county);
            }
        }

        public void OnExploreClick()
        {
            SceneManager.LoadScene("Explore");
        }

        public void OnSearchClick()
        {
            SceneManager.LoadScene("Search");
        }

        public void OnBookingsClick()
        {
            SceneManager.LoadScene("Bookings");
        }

        public void OnProfileClick()
        {
            SceneManager.LoadScene("Profile");
        }
    }

    public class CountyCard : MonoBehaviour
    {
        [SerializeField] private Text nameText;
        [SerializeField] private RawImage thumbnail;
        [SerializeField] private Button button;

        private CountyData _data;

        public void Setup(CountyData data)
        {
            _data = data;
            if (nameText != null) nameText.text = data.name;
            if (button != null)
                button.onClick.AddListener(() => OnCountyClicked());
        }

        private async void OnCountyClicked()
        {
            if (_data == null) return;
            PlayerPrefs.SetString("SELECTED_COUNTY_SLUG", _data.slug);
            PlayerPrefs.Save();
            SceneManager.LoadScene("CountyDetail");
        }
    }

    /// <summary>
    /// Helper to parse JSON arrays (Unity's JsonUtility doesn't support arrays directly)
    /// </summary>
    public static class JsonHelper
    {
        public static T[] FromJson<T>(string json)
        {
            var wrapper = JsonUtility.FromJson<Wrapper<T>>($"{{\"items\":{json}}}");
            return wrapper?.items;
        }

        [System.Serializable]
        private class Wrapper<T>
        {
            public T[] items;
        }
    }
}