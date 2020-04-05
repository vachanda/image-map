function pushToLocalStorage(keyName, value) {
    localStorage.setItem(keyName, JSON.stringify(value));
};

function getFromLocalStorage(keyName) {
    return JSON.parse(localStorage.getItem(keyName));
}

function processCoords() {
    if (validateCoordinates() === false) {
        return;
    }

    var topCoord = getFromLocalStorage("ipTopCoords");
    var botCoord = getFromLocalStorage("ipBotCoords");


    var c_topCoord = []; 
    var c_botCoord = [];
    c_topCoord = convertCoords(topCoord); // Convert the coords to the actual image size.
    c_botCoord = convertCoords(botCoord); // Convert the coords to the actual image size.

    var id = c_topCoord.join("-") + "-" + c_botCoord.join("-");
    createCoordsSvg(topCoord, botCoord, id);
    populateCoordsTable(c_topCoord, c_botCoord);

    pushToLocalStorage("ipTopCoords", []);
    pushToLocalStorage("ipBotCoords", []);
};

function createCoordsSvg(topCoord, botCoord, id) {
    var svgns = "http://www.w3.org/2000/svg";

    var width = botCoord[0] - topCoord[0];
    var height = botCoord[1] - topCoord[1];
    var x = topCoord[0] + 6;
    var y = botCoord[1] - 4;    

    if ($('.image-mapper-svg').find('#' + id).length === 0) {
        var rect = document.createElementNS(svgns, 'rect');
        rect.setAttributeNS(null, 'x', x);
        rect.setAttributeNS(null, 'y', y);
        rect.setAttributeNS(null, 'width', width);
        rect.setAttributeNS(null, 'height', height);
        rect.setAttributeNS(null, 'id', id);
        document.getElementById('image-mapper-svg').appendChild(rect);
    }
};

function convertCoords(cord) {
    var image = $('.image-mapper-img')[0];
    var dimRatio = image.naturalWidth / image.width;

    return cord.map(function(x) { return Math.round(x * dimRatio); });
};

function populateCoordsTable(topCoord, botCoord) {
    var coords = topCoord.join(", ") + ", " + botCoord.join(", ");
    var id = coords.replace(/, /g, '-');

    if ($('.image-mapper-data table tbody').find('#'+id).length === 0 ) {
        var tableMarkup = `<tr id='${id}'><td><input type="text" placeholder="coordinates" value="${coords}" class="input-disabled" readonly></td>` + "<td><input type='text' placeholder='target'/></td>\
            <td><input type='text' placeholder='alt'/></td>" + `<td><span aria_hidden='true' id='${id}' onclick='deleteEntry(event);'>` + "<ion-icon name='close-circle-sharp'></ion-icon></span></td></tr>";

        $(".image-mapper-data table tbody").append(tableMarkup);
    }
};

function camelize(text) {
    return text.replace(/^([A-Z])|[\s-_]+(\w)/g, function(match, p1, p2, offset) {
        if (p2) return p2.toUpperCase();
        return p1.toLowerCase();        
    });
}

function populateCoordsData(event) {
    var coordsData = [];
    var headers = [];

    $("#image-mapper-table th").each(function(index) {
        headers.push(camelize($(this).html()));
    });

    var $rows = $("#image-mapper-table tbody tr").each(function(index) {
        $cells = $(this).find("td");
        coordsData[index] = {};

        headers.forEach(function(header, cellIndex, headers) {
            coordsData[index][header] = $cells[cellIndex].firstElementChild.value;
        });
    });

    if (validatePayload(coordsData) === false) {
        return false;
    }

    var $rows = $("#image-mapper-table tbody tr").each(function(index) {
        $cells = $(this).find("td");

        headers.forEach(function(header, cellIndex, headers) {
            $cells[cellIndex].firstElementChild.readOnly = true;
            $cells[cellIndex].firstElementChild.classList.add('input-disabled');
        });
    });

    var delta = $('#image-map-form').children().length;
    
    coordsData.forEach(function(data, rowIndex, coordsData) {
        var id = "";
        var htmlPayload = ""
        for (var key in data) {
            if (id == false) {
                id = data[key].replace(/, /g, '-');
            }

            htmlPayload = htmlPayload.concat(`<input type="hidden" name="o-module-image-map:map[${rowIndex + delta}][o-module-mapping:${key.toLowerCase()}]" value="${data[key]}">`);
        }
        htmlPayload = `<div id="${id}">${htmlPayload}</div>`

        if ($('#image-map-form').find('#' + id).length === 0) {
            $("#image-map-form").append(htmlPayload);
        }
    });

    var successText = "<p><strong>Success!</strong> Populated the data. Please save the item.</p>"
    setAlertText(successText, "alert success");

    return true;
}

function closeAlertBox(event) {
    var div = event.srcElement.parentElement;
    div.style.opacity = "0";
    setTimeout(function(){ div.style.display = "none"; }, 600);

    if (div.classList.contains("alert")) {
        div.classList.remove("alert");
    }

    if (div.classList.contains("success")) {
        div.classList.remove("success");
    }

    div.style.opacity = null;
    div.style.display = null;
    div.removeChild(div.lastChild);
};

function setAlertText(message, addClass) {
    $('.image-map-alert').addClass(addClass);
    $('.image-map-alert').append(message);
    $('.image-map-alert').show();

    window.scrollTo(0, 0);
};

function validatePayload(coordsData) {
    if (coordsData.length === 0) {
        return false;
    }

    var status = true;

    coordsData.forEach(function(data, index, coordsData) {
        for(var key in data) {

            if (data[key] == false) {
                var alertText = `<p><strong>Error!</strong> Please fill the ${key} data in the coordinates table.</p>`;
                setAlertText(alertText, "alert");
                status = false;
                break;
            }
        }

        if (status === false) {
            return;
        }
    });

    return status;
};

function deleteEntry(event) {
    var curElem = event.currentTarget;
    var id = curElem.id;

    var tableRow = curElem.parentNode.parentNode;
    tableRow.parentNode.removeChild(tableRow);

    var svgElem = $('.image-mapper-svg').find('#' + id)[0];
    if (svgElem !== undefined ) {
        svgElem.parentNode.removeChild(svgElem);
    }

    $('#image-map-form').find('#'+id).remove();
};

function validateCoordinates() {
    var ipTopCoords = getFromLocalStorage("ipTopCoords");
    var ipBotCoords = getFromLocalStorage("ipBotCoords");

    if (ipTopCoords.length === 0) {
        return false;
    }

    if (ipBotCoords.length === 0) {
        return false;
    }

    if ((ipTopCoords[0] > ipBotCoords[0]) || (ipTopCoords[1] > ipBotCoords[1])) {
        var alertText = "<p><strong>Error!</strong> Please select valid Top left and Bottom right coordinates.</p>";
        setAlertText(alertText, "alert");

        pushToLocalStorage("ipTopCoords", []);
        pushToLocalStorage("ipBotCoords", []);

        return false;
    }

    return true;
};

function setupLocalStorage() {
    if (localStorage.getItem("ipTopCoords") === null) {
        localStorage.setItem("ipTopCoords", JSON.stringify([]));
    }

    if (localStorage.getItem("ipBotCoords") === null) {
        localStorage.setItem("ipBotCoords", JSON.stringify([]));
    }
};

window.onbeforeunload = function (e) {
    localStorage.clear();
};

$(document).ready(function() {
    setupLocalStorage();
    var curState = 0;

    $('.image-mapper-img').click(function(e) {
        var offset = $(this).offset();
        var x = Math.round(e.pageX - offset.left);
        var y = Math.round(e.pageY - offset.top);

        if (curState === 0 ) { /* It is the top right coordinates push to the respective array */
            pushToLocalStorage("ipTopCoords", [x, y]);
            curState = 1;
        } else {
            pushToLocalStorage("ipBotCoords", [x, y]);
            curState = 0;
        }

        processCoords();
    });
});