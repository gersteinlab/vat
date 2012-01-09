all-local:
	@echo '  GEN    vat.conf'
	@cp -r $(top_srcdir)/default.vatrc $(top_srcdir)/master/vat.conf
	@echo '  CP     css'
	@cp -r $(top_srcdir)/web/css $(top_srcdir)/master
	@echo '  CP     image'
	@cp -r $(top_srcdir)/web/image $(top_srcdir)/master
	@echo '  CP     js'
	@cp -r $(top_srcdir)/web/js $(top_srcdir)/master
	@echo '  CP     lib'
	@cp -r $(top_srcdir)/web/lib $(top_srcdir)/master

clean-local:
	rm -rf vat.conf css image js lib