using UnityEditor;
using UnityEditor.Build.Reporting;
using UnityEngine;

namespace KICC.Editor
{
    public static class BuildScript
    {
        public static void PerformBuild()
        {
            var args = System.Environment.GetCommandLineArgs();
            var platform = EditorUserBuildSettings.activeBuildTarget;

            string[] scenes = {
                "Assets/Scenes/Splash.unity",
                "Assets/Scenes/MainMenu.unity",
                "Assets/Scenes/Explore.unity",
                "Assets/Scenes/Search.unity",
                "Assets/Scenes/CountyDetail.unity",
                "Assets/Scenes/Viewer.unity",
                "Assets/Scenes/Profile.unity",
                "Assets/Scenes/ARViewer.unity",
            };

            var version = PlayerSettings.bundleVersion;
            Debug.Log($"[KICC Build] Starting build v{version} for {platform}");

            BuildReport report;
            if (platform == BuildTarget.Android)
            {
                var options = new BuildPlayerOptions
                {
                    scenes = scenes,
                    locationPathName = $"Builds/KICC-Explore-v{version}.apk",
                    target = BuildTarget.Android,
                    options = BuildOptions.CompressWithLz4HC | BuildOptions.StrictMode,
                };
                report = BuildPipeline.BuildPlayer(options);
            }
            else if (platform == BuildTarget.iOS)
            {
                var options = new BuildPlayerOptions
                {
                    scenes = scenes,
                    locationPathName = $"Builds/KICC-Explore-v{version}",
                    target = BuildTarget.iOS,
                    options = BuildOptions.StrictMode,
                };
                report = BuildPipeline.BuildPlayer(options);
            }
            else
            {
                Debug.LogError($"[KICC Build] Unsupported platform: {platform}");
                EditorApplication.Exit(1);
                return;
            }

            var summary = report.summary;
            if (summary.result == BuildResult.Succeeded)
            {
                Debug.Log($"[KICC Build] ✓ Success: {summary.totalSize} bytes, {summary.totalTime}");
                EditorApplication.Exit(0);
            }
            else
            {
                Debug.LogError($"[KICC Build] ✗ Failed: {summary.result}");
                EditorApplication.Exit(1);
            }
        }
    }
}