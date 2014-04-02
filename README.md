PHP5-NetworkAutomation
======================

Greetings reader: This is the fourth major installment of this package of libraries and example tools, but before you grab it and expect big things, I need a few moments to explain what this is and is not.


What PHP5-NetworkAutomation is NOT:
======================
This is NOT a pre-packaged all-in-one solution to fix your environment, wrapped up in a nice bow. These are not high quality tools from a software engineering expert.


What PHP5-NetworkAutomation is:
======================
A set of libraries and tools in use in several VERY large networks today that the authors suport, providing features to automate the provisioning, maintanence, and operation. They fill a very real gap left in the industry and several of us have tried (sometimes quite successfully) to fill.


So why publish it?
======================
There are people that may find pieces of this useful in solving their specific problems, and they are more than welcome to absorb and repurpose the code provided here. All code included that IS ours is LGPL licensed (some files that are ours dont have the proper header documentation though)

That said, some of us have managed to use these to move toward a 99% or better tool-configured network with SNMP autodiscovery and configuration provisioning, auditing, firewall management, etc. Some features are specifically designed around small carriers running their own MPLS backbone, while others are for back office access switches.


Hey, theres a lot of junk here...
======================
This collection was bundled up in many cases with unrelated libraries written by other people (and nobody here is taking credit for their great work) however due to the highly specific and complex nature of packet wrangling, in some cases the code that IS ours has become highly dependant on a specific version of their library, or in a few cases minor changes have been made to their code to resolve an issue, and as such it is included in this repository.

IF you are the author of one of the libraries that our stuff is dependant on, AND dont like that its included here, just drop me a note and I will be happy to remove it. As a convenience to the unlucky person that is trying to make this work however, i would beg that you permit me to leave it included as it is most likely an integral part of the puzzle.

Also, different pieces of these libraries and tools were written by different people over the course of the last 6 years, and have evolved substancially since their original inception. There are pieces that are poorly documented, and were provided more of an example to show implementation as opposed to a ready-to-use application.


So...
======================
I know that an uninitiated user will likely be unable to build something really meaningful out of these tinker toys on your own. There are chunks intentionally removed from this repository to protect some of the networks it supports. Other pieces are not included due to licensing concerns. If you are serious about committing time to developing your own tools using this code, you will likely have to work with one of the existing authors to get a functional solution.

With all of that out of the way, if you are still reading this document, there are some incomplete INSTALL instructions in the doc/ folder to explain the environment this code expects to live in, and with your mighty internet detective skills please feel free to track me down on linkedin or drop me an email asking questions about it: >your favorite routing protocol< >AT< SecureObscure.com 


Thanks for reading!

3


P.S.
======================
Unlike most programmers, we like tabs. So set your tab-width to 4 while reading this pasta programming spaghetti source
