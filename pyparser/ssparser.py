#!/usr/bin/env python
import sys, os
abspath = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.append(abspath)
os.chdir(abspath)
from pyparsing import *
import web

word = Word(alphanums+"()-,./?;:!&'\"")
finish = CaselessKeyword('*finish')
page_break = CaselessKeyword('*page_break')
line_break = CaselessKeyword('*line_break')
choice = CaselessKeyword('*choice')
hash_char = Suppress('#')
open_brace = Suppress('{')
close_brace = Suppress('}')
label = Group(CaselessKeyword('*label') + Suppress(open_brace) + OneOrMore(word) + Suppress(close_brace))
goto = Group(CaselessKeyword('*goto') + Suppress(open_brace) + OneOrMore(word) + Suppress(close_brace))
error = Group(CaselessKeyword('*error') + Suppress(open_brace) + OneOrMore(word) + Suppress(close_brace))

description = OneOrMore(word | line_break | page_break)
choice_block = Forward()
block = (Suppress(ZeroOrMore(line_break)) + (error | (Optional(description) + 
	                                                  (goto | choice_block | finish))) + 
         Suppress(ZeroOrMore(line_break)))
named_block = Group(label + block)
positional_block = Group(Optional(label) + block)
choice_desc = OneOrMore(word)
choice_item = hash_char + Group(choice_desc + Suppress(open_brace) + Group(OneOrMore(positional_block)) + Suppress(close_brace))
choice_block << Group(Suppress(choice) + Suppress(open_brace) + OneOrMore(choice_item) + Suppress(close_brace))
script = positional_block + ZeroOrMore(named_block) + stringEnd

description.setParseAction(lambda words: '<div class="page"><p>' + ' '.join(words) + '</p></div>')
line_break.setParseAction(lambda: '</p><p>')
page_break.setParseAction(lambda: '</p></div><div class="page"><p>')
choice_desc.setParseAction(lambda words: ' '.join(words))
goto.setParseAction(lambda tokens: {'type': 'goto', 'label': ' '.join(tokens[0][1:])})
label.setParseAction(lambda tokens: {'type': 'label', 'label': ' '.join(tokens[0][1:])})
error.setParseAction(lambda tokens: {'type': 'error', 'label': ' '.join(tokens[0][1:])})
choice_item.setParseAction(lambda tokens: {'type': 'choice_item', 'description': tokens[0][0], 'children': tokens[0][1:][0][0]})
choice_block.setParseAction(lambda tokens: {'type': 'choice_block', 'children': tokens[0]})

def parseBlock(tokens):
	result = {}
	for t in tokens[0]:
		if type(t) == str:
			if result.has_key('description'):
				result[description] += t
			else:
				result[description] = t
		elif type(t) == dict:
			if t['type'] in ['choice_block']:
				result[t['type']] = t
	return tokens[0]

# positional_block.setParseAction(parseBlock)
# named_block.setParseAction(parseBlock)

def preprocess_ws(infile, outfile):
	"""
	Proprocess indents and dedents by adding opening and closing braces.
	Replaces blank lines by adding *line_break commands.
	Adds braces to the arguments of single-line keywords (*error, *goto, *label).
	"""

	# The following keywords occur at the start of a line, are followed by one or more words as argument,
	# and the arguments end at the end of a line. Since the line end is significant whitespace, it is
	# preprocessed here by adding braces around the argument.
	keywords = ['*error', '*goto', '*label']

	indent_stack = []

	for line in infile:
		line = line.expandtabs(4)
		# print line
		indent = len(line) - len(line.lstrip())
		if line.isspace():
			if len(indent_stack) > 0:
				outfile.write(' '*indent_stack[-1] + '*line_break\n')
			else:
				outfile.write('*line_break\n')
		else:
			if (not line.isspace()) and (indent > 0):
				if len(indent_stack) == 0:
					# This is the first indent
					outfile.write('{\n')
					indent_stack.append(indent)
				else:
					if indent > indent_stack[-1]:
						# We've increased the indentation
						outfile.write(' '*indent_stack[-1] + '{\n')
						indent_stack.append(indent)
					else:
						while (len(indent_stack) > 0) and (indent < indent_stack[-1]):
							# We've decreased the indentation
							indent_stack.pop()
							if indent > indent_stack[-1]:
								raise IndentationError()
							outfile.write(' '*indent_stack[-1] + '}\n')
			# Process one-line commands
			words = line.lstrip().split()
			if words[0].lower() in keywords:
				words.insert(1,'{')
				words.append('}')
				outfile.write(' '*indent + ' '.join(words) + '\n')
			else:
				outfile.write(line)

	# Flush out any remaining dedents
	while len(indent_stack) > 1:
		indent = indent_stack.pop()
		outfile.write(' '*indent_stack[-1] + '}\n')

	if len(indent_stack) > 0:
		outfile.write('}')


class State(object):

	def __init__(self, tokens, stategraph, default_label):
		self.description = None
		self.successors = []
		self.label = default_label
		self.redirect = None

		if tokens:

			for t in tokens:
				if type(t) == str:
					if self.description:
						self.description += t
					else:
						self.description = t
				elif type(t) == dict:
					if t['type'] == 'choice_block':
						for (idx,choice) in enumerate(t['children']):
							successor_label = self.label + '-' + str(idx+1)
							successor_state = State(choice['children'], stategraph, successor_label)
							self.successors.append((choice['description'], successor_state.label))
					elif t['type'] == 'label':
						self.label = t['label']
					elif t['type'] == 'goto':
						self.redirect = t['label']

		stategraph[self.label] = self


web.wsgi.runwsgi = lambda func, addr=None: web.wsgi.runfcgi(func, addr)
if __name__ == '__main__':

	inf = open('/var/www/html/interactivefiction/pyparser/test.slc', 'r')
	outf = open('test.slcx', 'w')
	preprocess_ws(inf, outf)
	inf.close()
	outf.close()

	tokens = script.parseFile('test.slcx')

	stategraph = {}

	root = State(tokens[0], stategraph, 'root')
	state = root

	while True:
		D = str(state.description)
		print(D.replace('<p>','').replace('</p>','\n'))
		if state.redirect:
			state = stategraph[state.redirect]
		elif len(state.successors) == 0:
			print("Thank you for playing!\n")
			break
		else:
			for (number, (choice_desc, dest_state)) in enumerate(state.successors):
				print("%d) %s" % (number+1, choice_desc))
			choice = raw_input("\nType the number of your choice: ")
			state = stategraph[state.successors[int(choice)-1][1]]

