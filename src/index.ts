import "./style.scss"

import * as htmx from 'htmx.org/dist/htmx.js'
window.htmx = htmx

import "datatables.net"
import dt from "datatables.net-zf"
dt($)


import dlv from "@paxperscientiam/dlv.ts"
// @ts-ignore
window.dlv = dlv

import * as $ from "jquery"
// @ts-ignore
window.$ = $

import Inputmask from "inputmask"
// @ts-ignore
window.Inputmask = Inputmask


import select2 from "select2"

select2($);


import nb from "notifyjs-browser"
nb($);


import Foundation from 'foundation-sites'
Foundation.addToJquery($);


async function delay(duration = 0) {
    await new Promise(r => setTimeout(r, duration));
}

// credit to https://davidwalsh.name/javascript-debounce-function
function debounce(func: Function, wait: number, immediate: boolean) {
	  let timeout: null | number
	  return function() {
		    const context = <Window>this
        const args = arguments;
		    var later = function() {
			      timeout = null;
			      if (!immediate) func.apply(context, args);
		    };
		    var callNow = immediate && !timeout;
		    window.clearTimeout(timeout as number);
		    timeout = window.setTimeout(later, wait);
		    if (callNow) func.apply(context, args);
	  };
};

// @ts-ignore
window.debounce = debounce


interface IPayload {
    error: number|boolean,
    response: {
        message: string,
        data: any
    }
}

$(() => {
    $("select").select2();

    function saveCell(e: Event) {
        const $target = $(e.currentTarget);
        $target.removeClass("editing");
        let value = $target.text();
        const id = $target.attr("id");
        localStorage.setItem("dataStorage-"+id, value);

        const data = $target.data();
        const col = data.name;
        const row = data.rowid;

        if ("date" == col) {
            const [month, day, year] = value.split("/");
            value = [year, month, day].join("/");
        }

        $.post("/submit-edit-cell", {rowID: row, colID: col, value: value}, function(d: IPayload) {
            $.notify(dlv(d, "response.message"), "success");
        }, "json");
    };
    // @ts-ignore
    window.saveCell = saveCell


    $(document).on("mousedown touchstart", "table tr [name='delete-row-button']", (e: PointerEvent) => {
        const $button = $(e.currentTarget);
        const $row = $button.parents("tr");
        const rowID = $button.data("rowid");

        $.post("/submit-delete-row", `rowID=${rowID}`, function(d: IPayload) {
            if (0 != d.error) {
                $.notify(dlv(d, "response.message"), "error");
                return;
            }
            $row.fadeOut();
            $.notify(dlv(d, "response.message"), "info");
            delay(750).then(function() {
                location.reload()
            });
        });
    });


    $(document)
        .on("focus", "td[contenteditable]", (e: FocusEvent) => {
            const $target = $(e.currentTarget);
            const data = $target.data();
            //
            $target.addClass("editing");

            if (null == data.name) {
                return;
            }

            if ("quantity" == data.name) {
                Inputmask({"alias": "decimal"}).mask($target[0]);
            }

            if ("date" == data.name) {
                Inputmask({"alias": "datetime", inputFormat: "mm/dd/yyyy"}).mask($target[0]);
            }
        })

    Inputmask({"alias": "decimal"}).mask($("[name='amount']", "[name='food-log-form']")[0]);

    $(document).on("blur", "table td[contenteditable]", function(e: FocusEvent) {
        saveCell(e);
        $(e.currentTarget).removeClass("editing");
    });


    $("[name='drop-food-records']").on("click", function(e: Event) {
        e.preventDefault();
        fetch("/drop-food-log", {
            method: "POST",
            headers: {
                'Content-Type': 'application/json'
            },
        })
            .then(response => response.json())
            .then((json: IPayload) => {
                if (0 != json.error) {
                    $.notify(dlv(json, "response.message"), "error");
                    return;
                }
                $.notify(dlv(json, "response.message"), "success");
                delay(750).then(function() {
                    location.reload()
                });
            });

    });

    const dtSettings = {
        paging: false,
        lengthChange: false,
        searching: false,
    } as DataTables.Settings

    $("#journal-table").dataTable(dtSettings) as DataTables.Api

    $(document).foundation();
});
