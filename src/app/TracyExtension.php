<?PHP

namespace App;

use Tracy\IBarPanel;
use Illuminate\Database\Capsule\Manager as DB;

class TracyExtension implements IBarPanel
{
    public function getTab()
    {
        //return ...;
        $tab = <<<HTML
<span title="Explaining tooltip">
    <svg>....</svg>
    <span class="tracy-label">Queries</span>
</span>
HTML;
        return $tab;
    }

    public function getPanel()
    {
        $queries = array_column(DB::getQueryLog(), "query");
        $qstr = "";
        $counter = 1;
        $count = count($queries);
        foreach ($queries as $q) {
            $fq = \SqlFormatter::highlight($q);
            $qstr .= <<<HTML
<a href="#tracy-addons-className-{$counter}" class="tracy-toggle">{$counter}) Detail</a>
<br>
<div id="tracy-addons-className-{$counter}">{$fq}</div>
HTML;
            $counter++;
        }

        $panel = <<<HTML
<h1>queries ({$count})</h1>

<div class="tracy-inner">
<div class="tracy-inner-container">
    {$qstr}
</div>
</div>
HTML;
        return $panel;
    }
}
