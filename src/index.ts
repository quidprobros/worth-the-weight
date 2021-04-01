declare global {
    interface Window {
        Inputmask: any
        debounce: (func: Function, wait: number, immediate: boolean) => void
        delayedReload: (ms: number) => void
//        jsCalendar: any
        $: JQueryStatic
    }
}

import "./style.scss"

import $ from "jquery"
window.$ = $
export {$}

import Chart from 'chart.js'

import 'htmx.org/dist/htmx.min.js'
import 'htmx.org/dist/ext/path-deps.js'

import {jsCalendar} from 'simple-jscalendar/source/jsCalendar'
export {jsCalendar}

import dt from "datatables.net"
dt($)

import dlv from "@paxperscientiam/dlv.ts"
window.dlv = dlv
export {dlv}


import Inputmask from "inputmask"
export {Inputmask}

import SlimSelect from 'slim-select'

import notify from "notifyjs-browser"
notify(window, $)

import { Foundation } from 'foundation-sites/js/foundation.core'
Foundation.addToJquery($);
import {OffCanvas} from 'foundation-sites/js/foundation.offcanvas'


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

const datePair = [] as string[]
const dates = [] as Date[]


$(() => {

    new SlimSelect({
        select: '#food-selection'
    })

    const elCalendar = document.getElementById("my-jsCalendar");
    // @ts-ignore
    const myCalendar = new jsCalendar(elCalendar);

    myCalendar.onDateClick((event: Event, date: Date) => {
        const strDate = jsCalendar.tools.dateToString(date, "YYYY-MM-DD")
        if (true === myCalendar.isSelected(date)) {
            myCalendar.clearselect()
            return;
        }
        datePair.push(strDate)
        dates.push(date)

        if (2 < datePair.length) {
            datePair.shift()
            datePair.splice(2)
            //
            myCalendar.unselect(dates[0])
            dates.shift()
            dates.splice(2)
        }
        if (0 < datePair.length) {
            myCalendar.select(dates)
        }
        htmx.ajax('GET', `/journal/date/${datePair[0]}/${datePair[1]}`, "#calendar-date-data");
    })

    Inputmask({"alias": "decimal"}).mask($("[name='amount']", "[name='food-log-form']")[0]);

    const dtSettings = {
        paging: false,
        lengthChange: false,
        searching: false,
    } as DataTables.Settings

    $("#journal-table").DataTable(dtSettings) as DataTables.Api

    var myBarChart = new Chart("myChart", {
        type: 'bar',
        data: [0,1,3,4]
    });


    new OffCanvas($("#offCanvas"));
    new OffCanvas($("#offCanvas2"));
});
