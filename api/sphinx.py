import os
import sys

# Access command-line arguments
action = sys.argv[1]

if action == "start":
    #  update the index
    os.system("indexer --config /etc/sphinxsearch/sphinx.conf --all")
    # start the service 
    os.system("searchd --config /etc/sphinxsearch/sphinx.conf")
else:
    # stop the service
    os.system("searchd --config /etc/sphinxsearch/sphinx.conf --stop")