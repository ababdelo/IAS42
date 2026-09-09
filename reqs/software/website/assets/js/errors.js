"use strict";
document.addEventListener("DOMContentLoaded", function () {
    const errorDetails = {
        400: {
            title: "IAS42 : Bad Request",
            description: "The server cannot process the request due to a client error."
        },
        401: {
            title: "IAS42 : Unauthorized",
            description: "Authentication is required to access this resource."
        },
        403: {
            title: "IAS42 : Forbidden",
            description: "You don't have permission to access this resource."
        },
        404: {
            title: "IAS42 : Not Found",
            description: "The requested resource could not be found."
        },
        408: {
            title: "IAS42 : Request Timeout",
            description: "The server timed out waiting for the request."
        },
        500: {
            title: "IAS42 : Internal Server Error",
            description: "The server encountered an unexpected condition."
        },
        503: {
            title: "IAS42 : Service Unavailable",
            description: "The service is temporarily unavailable, please try again later."
        }
    };

    // Grab the code passed silently from PHP
    const errorCode = typeof SERVER_ERROR_CODE !== 'undefined' ? SERVER_ERROR_CODE : "404";
    const errorInfo = errorDetails[errorCode];

    if (errorInfo) {
        document.title = errorInfo.title;
        document.querySelector(".code-error").textContent = errorCode;
        document.querySelector(".error-title").textContent = errorInfo.title.replace("IAS42: ", "");
        document.querySelector("#source").textContent = errorInfo.description;
    } else {
        document.title = "IAS42: Unknown Error";
        document.querySelector(".error-title").textContent = "Unknown Error";
        document.querySelector("#source").textContent = "An unknown error has occurred.";
    }
});
