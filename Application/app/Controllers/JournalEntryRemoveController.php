<?PHP

namespace App\Controllers;

use Exception;
use Flight;

class JournalEntryRemoveController
{
    private $journal_entry_id;

    public function deleteEntry($journal_entry_id)
    {
        if (true != is_numeric($journal_entry_id)) {
            throw new Exception("Bad id value: ${$journal_entry_id}");
        }

        $this->journal_entry_id = (int) $journal_entry_id;
        $item =  Flight::get('ActiveUser')->journal()->findOrFail($this->journal_entry_id);
        $item->delete();
    }

    public function deleteAll()
    {
        Flight::get('ActiveUser')->journal()->truncate();
    }
}
