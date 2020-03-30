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

    createCoordsSvg(topCoord, botCoord);
    populateCoordsTable(topCoord, botCoord);

    pushToLocalStorage("ipTopCoords", []);
    pushToLocalStorage("ipBotCoords", []);
};

function createCoordsSvg(topCoord, botCoord) {
    var svgns = "http://www.w3.org/2000/svg";

    var width = botCoord[0] - topCoord[0];
    var height = botCoord[1] - topCoord[1];
    var x = topCoord[0] + 6;
    var y = botCoord[1] - 4;
    var id = topCoord.join("-") + "-" + botCoord.join("-");

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

function populateCoordsTable(topCoord, botCoord) {
    var coords = topCoord.join(", ") + ", " + botCoord.join(", ");
    var id = coords.replace(/, /g, '-');

    if ($('.image-mapper-data table tbody').find('#'+id).length === 0 ) {
        var tableMarkup = `<tr id='${id}'>` + "<td>" + coords + "</td><td><input type='text' placeholder='Alt Text'/></td>\
            <td><input type='text' placeholder='Link'/></td>" + `<td><span aria_hidden='true' id='${id}' onclick='deleteEntry(event);'>` + "<ion-icon name='close-circle-sharp'></ion-icon></span></td></tr>";

        $(".image-mapper-data table tbody").append(tableMarkup);
    }
};

function camelize(text) {
    return text.replace(/^([A-Z])|[\s-_]+(\w)/g, function(match, p1, p2, offset) {
        if (p2) return p2.toUpperCase();
        return p1.toLowerCase();        
    });
}

function submitCoords(event) {
    var coordsData = [];
    var headers = [];
    var payload = Object();

    $("#image-mapper-table th").each(function(index) {
        headers.push(camelize($(this).html()));
    });

    var $rows = $("#image-mapper-table tbody tr").each(function(index) {
        $cells = $(this).find("td");
        coordsData[index] = {};

        headers.forEach(function(header, cellIndex, headers) {
            var value = "";
            if ($cells[cellIndex].childElementCount > 0) {
                value = $cells[cellIndex].firstElementChild.value;
            } else {
                value = $($cells[cellIndex]).html();
            }

            coordsData[index][header] = value;
        });
    });

    if (validatePayload(coordsData) === false) {
        return;
    }

    payload.data = coordsData;

    $.ajax({
        type: 'POST',
        url: '/admin/api/image-map',
        data: JSON.stringify(payload),
        contentType: 'application/json',
        dataType: 'json',
        success: function(results) {
            alert("Result: " + JSON.stringify(results));
        },
    });

    debugger;
};

function validatePayload(coordsData) {
    if (coordsData.length === 0) {
        return false
    }

    coordsData.forEach(function(data, index, coordsData) {
        for(var key in data) {
            if (data[key] == false) {
                alertText = `Please fill the ${key} data in the coordinates table.`;
                alert(alertText);

                return false;
            }
        }
    });

    return true;
};

function deleteEntry(event) {
    var curElem = event.currentTarget;
    var id = curElem.id;

    var tableRow = curElem.parentNode.parentNode;
    tableRow.parentNode.removeChild(tableRow);

    var svgElem = $('.image-mapper-svg').find('#' + id)[0];
    svgElem.parentNode.removeChild(svgElem);
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
        alert("Please select valid Top left and Bottom right coordinates.");
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