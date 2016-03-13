NAME=$(shell basename `pwd`)
RESOURCES_NAMES=css/site.css font-awesome/css font-awesome/fonts manifest.json sortable/Sortable.min.js img

RESOURCES_SRC=$(addprefix resources/public/, $(RESOURCES_NAMES))
RESOURCES=$(addprefix build/, $(RESOURCES_NAMES))
IDX=build/index.html
APP=build/js/app.js
SERVER=build/server.php

TARGETS=$(RESOURCES) $(IDX) $(APP) $(SERVER)

all: $(TARGETS)

$(RESOURCES): $(RESOURCES_SRC)
	@echo "Copying resources:" $@
	@mkdir -p `dirname $@`
	@cp -avr $(subst build, resources/public, $@) $@
	@touch $@

$(APP): src/**/** project.clj
	rm -f $(APP)
	lein cljsbuild once min

$(SERVER): server.php
	cp server.php $@
	mkdir -p build/data

$(IDX): src/clj/*/*.clj
	lein run -m $(NAME).handler/index-html > $(IDX)

clean:
	rm -rf $(TARGETS) build
