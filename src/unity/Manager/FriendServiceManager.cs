using System.Threading.Tasks;
using UnityEngine;

public class FriendServiceManager
{
    private enum FriendService{
        searchFriend,
        clickFriendAction,
        acceptFriendRequest,
        rejectFriendRequest,
        deleteFriend,
        getFriendRequestListData,
        getFriendListData,
        getFriendshipStatus
    }

    private readonly static string ENDPOINT = "friendSystem.php";
    private DataRequest dataRequest; //MainSceneManager/UserData

    public FriendServiceManager(string senderID, string receiverID){

        dataRequest = new DataRequest{
            SenderID = senderID,
            ReceiverID = receiverID
        };
    }

    private async Task<T> SendRequest<T>(FriendService action){

        string sendTo = $"{APIManager.SERVER_URL}/{ENDPOINT}";

        dataRequest.Action = action.ToString();

        try{
            var (success, data) = await APIManager.SendJsonPostRequest<T>(sendTo, dataRequest);

            if(!success){
                Debug.Log("FAIL in FriendServiceManager: SendRequest");
                return default;
            }

            return data;
        }
        catch(System.Exception e){
            Debug.Log("FAIL in APIManager: " + e.Message);
            return default;
        }

    }

    public async Task<T> SearchFriend<T>(){
        return await SendRequest<T>(FriendService.searchFriend);
    }

    public async Task<T> ClickFriendAction<T>(){
        return await SendRequest<T>(FriendService.clickFriendAction);
    }

    public async Task<T> RejectFriendRequest<T>(){
        return await SendRequest<T>(FriendService.rejectFriendRequest);
    }

    public async Task<T> DeleteFriend<T>(){
        return await SendRequest<T>(FriendService.deleteFriend);
    }

    public async Task<T> GetFriendRequestData<T>(){
        return await SendRequest<T>(FriendService.getFriendRequestListData);
    }

    public async Task<T> GetFriendListData<T>(){
        return await SendRequest<T>(FriendService.getFriendListData);
    }

    public async Task<T> GetFriendshipStatus<T>(){
        return await SendRequest<T>(FriendService.getFriendshipStatus);
    }

}
