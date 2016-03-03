import os
import sys

import sphinx_rtd_theme
html_theme = "sphinx_rtd_theme"
html_theme_path = [sphinx_rtd_theme.get_html_theme_path()]

extensions = ["sphinxcontrib.phpdomain"]

master_doc = 'index'
project = u'Ctrl Common'
copyright = u'2016, Eric Holscher, Charlie Leifer, Bobby Grace'
source_suffix = ['.rst', '.md']
author = u'Nicky De Maeyer'

# Set up PHP syntax highlights
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer
lexers["php"] = PhpLexer(startinline=True, linenos=1)
lexers["php-annotations"] = PhpLexer(startinline=True, linenos=1)
primary_domain = "php"
