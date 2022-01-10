<?PHP

    if(!function_exists('o')) {
	    /**
	     * Pass an unlimited number of arguments. The first one that isn't empty is returned.
	     *
	     * @param string	Unlimited string based params
	     * @return string
	     */
	    function o() {
		    foreach(func_get_args() as $arg) {
				if(!empty($arg) || '0' === $arg || is_numeric($arg)) {
					return $arg;
				}
		    }
		    return '--';
	    }
    }
