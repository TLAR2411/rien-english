export const app = () => {
    const apps = localStorage.getItem("app");
    let appObject = null;
    if (apps) {
        appObject = JSON.parse(apps);
    } else {
        console.log("No app data found in local storage.");
    }
    return appObject;
};

