using UnityEngine;
using UnityEngine.SceneManagement;
using TMPro;
using System.Collections.Generic;
using System.Threading.Tasks;

public class LoginManager : MonoBehaviour
{
    [Header("Register")]
    [SerializeField] private TMP_InputField Reg_username;
    [SerializeField] private TMP_InputField Reg_password;
    [SerializeField] private TMP_Text Reg_error;

    [Header("Login")]
    [SerializeField] private TMP_InputField Log_username;
    [SerializeField] private TMP_InputField Log_password;
    [SerializeField] private TMP_Text Log_error;

    public static UserData UserData {get; private set;}


    public async void OnRegisterButtonClicked(){

        if(string.IsNullOrEmpty(Reg_username.text) || Reg_username.text.Length < 4){
            Reg_error.text = "Plese enter a valid Username";
            return;
        }
        else if(string.IsNullOrEmpty(Reg_password.text) || Reg_password.text.Length < 4){
            Reg_error.text = "Plese enter a valid Password";
            return;
        }

        if(await RegisterUser(Reg_username.text, Reg_password.text)){
            print("Success Registered!");
        }
        else{
            print("fail");
        }

    }

    public async void OnLoginButtonClicked(){

        if(string.IsNullOrEmpty(Log_username.text) || Log_username.text.Length < 4){
            Log_error.text = "Plese enter a valid Username";
            return;
        }
        else if(string.IsNullOrEmpty(Log_password.text) || Log_password.text.Length < 4){
            Log_error.text = "Plese enter a valid Password";
            return;
        }

        if(await LoginUser(Log_username.text, Log_password.text)){
            print("Login Success");
            SceneManager.LoadScene("MainScene");
        }
        else{
            print("Login Fail");
        }
        
    }

    private async Task<bool> RegisterUser(string username, string password) {

        string REGISTER = "register.php";

        string sendTo = $"{APIManager.SERVER_URL}/{REGISTER}";

        var (status, data) = await APIManager.SendFormPostRequest<JsonResponse>(sendTo, new Dictionary<string, string>(){
            { "username", username},
            { "password", password}
        });

        return status;
    }

    private async Task<bool> LoginUser(string username, string password) {
        
        string LOGIN = "login.php";

        string sendTo = $"{APIManager.SERVER_URL}/{LOGIN}";

        var (status, jsonResponse) = await APIManager.SendFormPostRequest<JsonResponse>(sendTo, new Dictionary<string, string>(){
            { "username", username},
            { "password", password}
        });

        UserData = jsonResponse.data;

        return status;
    }
}
