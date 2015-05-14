#!/usr/bin/env python

import os
import sys
import commands
import traceback
from optparse import OptionParser


class CheckSyntax:
    svnlook = "/usr/local/subversion/bin/svnlook"
    repo_path = ""
    txn = ""
    temp_dir = "/tmp"


    def __init__(self, repo_path, txn):
        self.repo_path = repo_path
        self.txn = txn


    def run(self):
        return [
            valid_file for valid_file in self.get_valid_files() 
            if self.is_file_syntax_error(valid_file)
        ]	


    def get_valid_files(self):
        cmd = "%s changed %s -t %s" % (self.svnlook, self.repo_path, self.txn)
        valid_files = [
            self.get_filepath(line) for line in self.run_command(cmd).split("\n")
            if self.is_valid_file(line) and self.is_php_file(line)
            ]
        return valid_files


    def get_filepath(self, line):
        return line[4:]


    def is_php_file(self, line):
        return os.path.splitext(line)[1] == ".php"


    def is_valid_file(self, line):
        return line and line[0] in ("A", "U", "UU")


    def is_file_syntax_error(self, file_path):
        temp_file = os.path.join(self.temp_dir, os.path.basename(file_path))
        self.run_command("%s cat %s %s -t %s > %s" % (self.svnlook, self.repo_path, file_path, self.txn, temp_file))
        line = self.run_command("/usr/bin/php -l %s 2>&1 | grep 'No syntax errors detected' | wc -l" % (temp_file, ))
        self.run_command("rm %s" % (temp_file, ))

        return int(line) != 1 


    def run_command(self, cmd):
        return commands.getoutput(cmd)



if __name__ == "__main__":
    usage = """
        Usage: %prog REPOS TXN
        """
    parser = OptionParser(usage=usage)
    error_message = ""
    try:
        (opts, (repo_path, txn)) = parser.parse_args()
        checker = CheckSyntax(repo_path, txn)
        error_files = checker.run()
        if len(error_files):
            error_message = "syntax error:\n\t" + "\n\t".join(error_files)
    except:
        info = sys.exc_info()
        for e_file, e_lineno, e_function, e_text in traceback.extract_tb(info[2]):
            error_message = "%s line: %s in %s\n %s" % (e_file, e_lineno, e_function, e_text)
		
    sys.stdout.write(error_message)


