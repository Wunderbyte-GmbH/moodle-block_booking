function setpageurlwithjs(Y, pageurl)
{
    const nextState = { additionalInformation: 'Updated the URL with JS' };

    // This will create a new entry in the browser's history, without reloading
    window.history.pushState(nextState, '', pageurl);

    // This will replace the current entry in the browser's history, without reloading
    window.history.replaceState(nextState, '', pageurl);
}