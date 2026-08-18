using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text;
using System.Threading.Tasks;
using UnityEngine;

namespace KICC.API
{
    public sealed class ApiClient
    {
        private static readonly Lazy<ApiClient> _instance = new Lazy<ApiClient>(() => new ApiClient());
        public static ApiClient Instance => _instance.Value;

        private readonly HttpClient _http;
        private string _baseUrl = "https://kicctest.org/api";
        private string _token;

        private ApiClient()
        {
            _http = new HttpClient { Timeout = TimeSpan.FromSeconds(30) };
            _http.DefaultRequestHeaders.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));
        }

        public void SetToken(string token)
        {
            _token = token;
            _http.DefaultRequestHeaders.Authorization = string.IsNullOrEmpty(token)
                ? null
                : new AuthenticationHeaderValue("Bearer", token);
        }

        public async Task<ApiResponse<T>> Get<T>(string endpoint)
        {
            try
            {
                var response = await _http.GetAsync($"{_baseUrl}{endpoint}");
                var body = await response.Content.ReadAsStringAsync();
                return new ApiResponse<T>(response.StatusCode, body);
            }
            catch (Exception ex)
            {
                Debug.LogError($"[KICC API] GET {endpoint} failed: {ex.Message}");
                return new ApiResponse<T>(System.Net.HttpStatusCode.ServiceUnavailable, ex.Message);
            }
        }

        public async Task<ApiResponse<T>> Post<T>(string endpoint, object data = null)
        {
            try
            {
                var json = data != null ? JsonUtility.ToJson(data) : "{}";
                var content = new StringContent(json, Encoding.UTF8, "application/json");
                var response = await _http.PostAsync($"{_baseUrl}{endpoint}", content);
                var body = await response.Content.ReadAsStringAsync();
                return new ApiResponse<T>(response.StatusCode, body);
            }
            catch (Exception ex)
            {
                Debug.LogError($"[KICC API] POST {endpoint} failed: {ex.Message}");
                return new ApiResponse<T>(System.Net.HttpStatusCode.ServiceUnavailable, ex.Message);
            }
        }

        public async Task<ApiResponse<LoginResponse>> Login(string email, string password)
        {
            return await Post<LoginResponse>("/auth/login", new { email, password });
        }

        public async Task<ApiResponse<CountiesResponse>> GetCounties()
        {
            return await Get<CountiesResponse>("/counties");
        }

        public async Task<ApiResponse<ExhibitionsResponse>> GetExhibitions()
        {
            return await Get<ExhibitionsResponse>("/exhibitions");
        }

        public async Task<ApiResponse<SearchResponse>> Search(string query)
        {
            return await Get<SearchResponse>($"/search/semantic?q={Uri.EscapeDataString(query)}");
        }

        public async Task<ApiResponse<AdResponse>> GetAd(string placement)
        {
            return await Get<AdResponse>($"/ads/serve/{placement}");
        }
    }

    [Serializable]
    public class ApiResponse<T>
    {
        public System.Net.HttpStatusCode StatusCode { get; }
        public T Data { get; }
        public string Raw { get; }
        public bool IsSuccess => (int)StatusCode >= 200 && (int)StatusCode < 300;

        public ApiResponse(System.Net.HttpStatusCode status, string raw)
        {
            StatusCode = status;
            Raw = raw;
            try { Data = JsonUtility.FromJson<T>(raw); }
            catch { Data = default; }
        }
    }

    [Serializable]
    public class LoginResponse
    {
        public UserData user;
        public string token;
    }

    [Serializable]
    public class UserData
    {
        public string id;
        public string uuid;
        public string name;
        public string email;
        public string account_type;
    }

    [Serializable]
    public class CountyData
    {
        public int id;
        public string name;
        public string slug;
        public string description;
        public string image_url;
    }

    [Serializable]
    public class CountiesResponse
    {
        public CountyData[] data;
    }

    [Serializable]
    public class ExhibitionData
    {
        public int id;
        public string name;
        public string slug;
        public string description;
        public string start_date;
        public string venue_name;
    }

    [Serializable]
    public class ExhibitionsResponse
    {
        public ExhibitionData[] data;
    }

    [Serializable]
    public class SearchResponse
    {
        public SearchResult[] results;
    }

    [Serializable]
    public class SearchResult
    {
        public string id;
        public string type;
        public string name;
        public string description;
        public float score;
    }

    [Serializable]
    public class AdResponse
    {
        public AdData ad;
    }

    [Serializable]
    public class AdData
    {
        public int creative_id;
        public bool sponsored;
        public string headline;
        public string description;
        public string image_url;
        public string click_url;
    }
}