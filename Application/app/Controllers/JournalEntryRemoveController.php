<?PHP

namespace App\Controllers;

use Exception;
use flight\Engine;

class JournalEntryRemoveController
{
    private $journal_entry_id;

    public function deleteEntry($journal_entry_id)
    {
        if (true != is_numeric($journal_entry_id)) {
            throw new Exception("Bad id value: ${$journal_entry_id}");
        }

        $this->journal_entry_id = (int) $journal_entry_id;
        $item =  $this->app->get('ActiveUser')->journal()->findOrFail($this->journal_entry_id);
        $item->delete();
    }

    public function deleteAll()
    {
        $this->app->get('ActiveUser')->journal()->truncate();
    }
}
