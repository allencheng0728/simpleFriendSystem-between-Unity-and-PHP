using System.Collections;
using System.Collections.Generic;
using UnityEngine;
using UnityEngine.UI;
using UnityEngine.SceneManagement;

public class FriendWindowManager : MonoBehaviour
{
    [SerializeField] private Button friendButton;
    [SerializeField] private Button requestButton;
    [SerializeField] private Button sentButton;
    [SerializeField] private Button returnMainButton;

    [SerializeField] private GameObject friendPanel;
    [SerializeField] private GameObject requestPanel;
    [SerializeField] private GameObject sentPanel;
    // Start is called before the first frame update
    void Start()
    {
        ShowPanel(friendPanel); // default panel

        friendButton.onClick.AddListener(() => ShowPanel(friendPanel));
        requestButton.onClick.AddListener(() => ShowPanel(requestPanel));
        sentButton.onClick.AddListener(() => ShowPanel(sentPanel));
        returnMainButton.onClick.AddListener(() => SceneManager.LoadScene("MainScene"));
    }

    // Update is called once per frame
    void Update()
    {
        
    }

    private void ShowPanel(GameObject showThisPanel){
        HideAllPanel();
        showThisPanel.SetActive(true);
    }

    private void HideAllPanel(){
        friendPanel.SetActive(false);
        requestPanel.SetActive(false);
        sentPanel.SetActive(false);
    }
}

/*
1. friend list by default, hide other
    HideAll
2. show panel , input object, set to active
3. add onclick.AddListener Event to each Button
*/
