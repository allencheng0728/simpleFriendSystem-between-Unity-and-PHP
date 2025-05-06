using System.Collections.Generic;
using System.Threading.Tasks;
using UnityEngine;
using UnityEngine.Networking;

public class APIManager
{   
    public readonly static string SERVER_URL = "http://localhost/gameDemo/";

    public static async Task<(bool success, T data)> SendJsonPostRequest<T>(string url, object dataRequest){

        // --> Json --> byte[]
        string jsonRequest = JsonUtility.ToJson(dataRequest);
        
        using (UnityWebRequest request = new UnityWebRequest(url, "POST")){
            
            // request setup before send request
            byte[] bytes = System.Text.Encoding.UTF8.GetBytes(jsonRequest);
            request.uploadHandler = new UploadHandlerRaw(bytes);
            request.downloadHandler = new DownloadHandlerBuffer();
            request.SetRequestHeader("Content-Type", "application/json");

            request.SendWebRequest();

            Debug.Log("sent");

            while(!request.isDone) await Task.Delay(100);

            if(request.result == UnityWebRequest.Result.Success){
                
                try{
                    Debug.Log(request.downloadHandler.text);
                    T responseData = JsonUtility.FromJson<T>(request.downloadHandler.text);
                    Debug.Log("get it");
                    return (true, responseData);
                }
                catch(System.Exception e){
                    Debug.Log("in APIManager: " + e.Message);

                    return (false, default);
                }

            }
            Debug.Log("request fail:" + request.error);
        }

        return (false, default);
    }

    public static async Task<(bool success, T data)> SendFormPostRequest<T>(string url, Dictionary<string, string> data){

        using (UnityWebRequest request = UnityWebRequest.Post(url, data)){
            
            request.SendWebRequest();

            Debug.Log("sent");

            while(!request.isDone) await Task.Delay(100);

            if(request.result == UnityWebRequest.Result.Success){

                Debug.Log(request.downloadHandler.text);

                T responseData = JsonUtility.FromJson<T>(request.downloadHandler.text);

                return (true, responseData);    
            }
            else{
                return (false, default);
            }
        }
    }
}
