/**
 * VANILLA STUFF
 */

function getElementAsHtml(element) {
    var tmp = document.createElement('div');
    tmp.appendChild(element);
    return tmp.innerHTML;
}

function addNewRow(formId, templateRowId) {
    var row = document.getElementById(templateRowId);
    var form = document.getElementById(formId);

    var newRow = document.createElement('tr');
    newRow.innerHTML = row.innerHTML;

    var tbody = form.getElementsByTagName('tbody');
    tbody[0].appendChild(newRow);
}

function removeElementById(id) {
    el = document.getElementById(id);
    el.parentNode.removeChild(el);
    return true;
}

/**
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 */