declare global {
    interface Window {
        Inputmask: any
        debounce: (func: Function, wait: number, immediate: boolean) => void
        delayedReload: (ms: number) => void
        $: JQueryStatic
    }
}

import "./style.scss"
import "node_modules/simple-jscalendar/source/jsCalendar.css"
import "node_modules/simple-jscalendar/themes/jsCalendar.darkseries.css"

import {App} from "./app"

export {App}

import $ from "jquery"
window.$ = $
export {$}

$("html").removeClass("no-js");

import 'htmx.org/dist/htmx.min.js'
import 'htmx.org/dist/ext/path-deps.js'
import 'htmx.org/dist/ext/class-tools.js'

import {jsCalendar} from 'simple-jscalendar/source/jsCalendar'
export {jsCalendar}

import dt from "datatables.net"
dt($)

import dlv from "@paxperscientiam/dlv.ts"
export {dlv}

import Inputmask from "inputmask"
export {Inputmask}

import SlimSelect from 'slim-select'

// @ts-ignore
import notify from "notifyjs-browser"
notify(window, $)

import {OffCanvas, Reveal} from 'foundation-sites'


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

const siteData = {
    calendarSelection: [] as {date: string, points: number}[]
}

export {siteData}


export function initCalendar() {
    const elCalendar = document.getElementById("my-jsCalendar");
    if (null == elCalendar) return
    // @ts-ignore
    const myCalendar = new jsCalendar(elCalendar, new Date(), {
        dayFormat: "DDD",
        monthFormat: "MONTH YYYY"
    });
        -
        myCalendar.onDateClick((_event: Event, date: Date) => {
            // @ts-ignore
            const strDate = jsCalendar.tools.dateToString(date, "YYYY-MM-DD") as string
            fetch(`/modals/go-to-date-modal/${strDate}`, {
                method: "GET",
                headers: {
                    'Content-Type': 'text/html'
                },
            })
                .then(response => response.text())
                .then(text => {
                    const $text = $(text)
                    const modal = new Reveal($text)
                    modal.open()

                    $("[data-accept]", $text).on("click", () => {
                        window.location.pathname = `/goto/${strDate}`
                    });

                    $text.on("closed.zf.reveal", () => {
                        $text.remove()
                    })
                })
                .catch(err => {
                    console.log(err)
                })
        })

    myCalendar.onMonthRender(<T extends {start: Date, end:Date}>(_index: number, _element: HTMLElement, info: T) => {
        // @ts-ignore
        const min = jsCalendar.tools.dateToString(info.start, "YYYY-MM-DD") as string
        // @ts-ignore
        const max = jsCalendar.tools.dateToString(info.end, "YYYY-MM-DD") as string

        if (true !== myCalendar._isSelectionComplete) {
            fetch(`/beef/${min}/${max}`, {
                method: "GET",
                headers: {
                    'Content-Type': 'application/json'
                },
            })
                .then(response => response.json())
                .then((json) => {
                    console.log(json)
                    if (Array.isArray(json.output)) {
                        json.output.forEach(<T extends {date:string,points:number}>(item: T) => {
                            const date = item.date // this is CORRECT
                            const points = item.points

                            siteData.calendarSelection.push({date, points})

                            myCalendar.select(new Date(`${date} 00:00:00`), false)
                        })
                    }
                })
                .finally(() => {
                    myCalendar._isSelectionComplete = true
                    myCalendar.refresh()
                    myCalendar._isSelectionComplete = false
                })
        }
    })

    myCalendar.onDateRender(<T extends {isSelected: boolean, isCurrentMonth: boolean}>(date: Date, element: HTMLElement, info: T) => {
        if (true == info.isSelected) {
            element.style.fontWeight = 'bold';
			      element.style.color = (info.isCurrentMonth) ? '#c32525' : '#ffb4b4';
            siteData.calendarSelection.forEach((item) => {
                const strDate = jsCalendar.tools.dateToString(date, "YYYY-MM-DD") as string
                if (strDate == item.date) {
                    element.dataset.microtipPosition = "top-right"
                    element.setAttribute("aria-label", `${item.points} points`)
                    element.setAttribute("role", "tooltip")
                }
            })
        }
	  });
	  // Refresh layout
	  myCalendar.refresh();
}

$(() => {
    console.debug("test1", 'page is ready')
    if (0 < $("#food-selection").length) {
        new SlimSelect({
            select: '#food-selection',
            allowDeselect: true,
            allowDeselectOption: true,
            placeholder: "Select food ...",
        })
    }

    if (0 < $("[name='food-log-form']").length) {
        Inputmask({"alias": "decimal"}).mask($("[name='amount']", "[name='food-log-form']")[0]);
    }

    if (0 < $("#journal-table").length) {
        const dtSettings = {
            paging: false,
            lengthChange: false,
            searching: false,
        } as DataTables.Settings

        $("#journal-table").DataTable(dtSettings) as DataTables.Api
    }

    initCalendar()

    if (0 < $("#offCanvas").length) {
        new OffCanvas($("#offCanvas"))
    }
    if (0 < $("#offCanvas2").length) {
        new OffCanvas($("#offCanvas2"))
    }

    $(document).on("click",  "#show-user-settings-button", function () {
        fetch(`/modals/user-settings`, {
            method: "GET",
            headers: {
                'Content-Type': 'text/html'
            },
        })
            .then(response => response.text())
            .then(text => {

                const $text = $(text)
                const modal = new Reveal($text)
                htmx.process(modal.$element[0]);

                if (0 < $("[name='user-settings-form']").length) {
                    // Inputmask({"alias": "decimal"}).mask($("[name='plan-points-goal']", "[name='user-settings-form']")[0]);
                }

                
                $("form", modal.$element).on("submit", () => {
                   // delayedReload(300);
                });

                $(`#${modal.id}`).on('closed.zf.reveal', function (e) {
                    e.currentTarget.remove();
                });

                new SlimSelect({
                    select: '#plan-selection',
                    allowDeselect: true,
                    allowDeselectOption: true,
                    addToBody: true,
                })

                modal.open();
            })
            .catch(err => {
                console.log(err)
            })

    });

    $(document).on("click",  "#show-user-vitals-button", function () {
        fetch(`/modals/user-vitals`, {
            method: "GET",
            headers: {
                'Content-Type': 'text/html'
            },
        })
            .then(response => response.text())
            .then(text => {
                const $text = $(text)
                const modal = new Reveal($text)
                htmx.process(modal.$element[0]);

                $("form", modal.$element).on("submit", () => {
                   // delayedReload(300);
                });

                $(`#${modal.id}`).on('closed.zf.reveal', function (e) {
                    e.currentTarget.remove();
                });

                // new SlimSelect({
                //     select: '#plan-selection',
                //     allowDeselect: true,
                //     allowDeselectOption: true,
                //     addToBody: true,
                // })

                modal.open();
            })
            .catch(err => {
                console.log(err)
            })

    });


    $(document).on("action", function (e) {
        dlv(App, dlv(e, "detail.xpath") as string)
    })

    $(document).on("showMessage", function (e) {
        if (dlv(e, "detail.message")) {
            // @ts-ignore
            $.notify(e.detail.message, e.detail.level);
        } else {
            $.notify("unknown message", "success");
        }
    });

});


