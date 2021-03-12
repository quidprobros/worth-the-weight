declare global {
    interface Window {
        Inputmask: any
        debounce: (func: Function, wait: number, immediate: boolean) => void
        delayedReload: (ms: number) => void

        $: any
    }
}


import "./style.scss"

import $ from "jquery"
window.$ = $

import 'htmx.org/dist/htmx.min.js'
import 'htmx.org/dist/ext/path-deps.js'


import dt from "datatables.net"
dt($)

import dlv from "@paxperscientiam/dlv.ts"
window.dlv = dlv


// //window.htmx.config.historyEnabled = false


import Inputmask from "inputmask"
window.Inputmask = Inputmask


import * as select2 from "select2"
$.fn.select2 = select2

import "notifyjs-browser"

import 'foundation-sites'

async function delay(duration = 0) {
    await new Promise(r => setTimeout(r, duration));
}

// extended from https://davidwalsh.name/javascript-debounce-function
function debounce(func: Function, wait: number, immediate: boolean) {
	  let timeout: null | number
	  return function() {
		    const context = window
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
window.debounce = debounce

function delayedReload(ms: number) {
    delay(ms).then(() => location.reload())
}
window.delayedReload = delayedReload


$(() => {
    $("select").select2();

    Inputmask({"alias": "decimal"}).mask($("[name='amount']", "[name='food-log-form']")[0]);

    const dtSettings = {
        paging: false,
        lengthChange: false,
        searching: false,
    } as DataTables.Settings

    $("#journal-table").DataTable(dtSettings) as DataTables.Api

    $(document).foundation();
});
