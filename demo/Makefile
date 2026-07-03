# Makefile for Password Strength Bundle Demo
# Symfony 8 demo under demo/symfony8/

DEMOS := symfony8
DEFAULT_PORT := 8021

.PHONY: help clean test-all release-check release-verify
.PHONY: $(foreach demo,$(DEMOS),up-$(demo) down-$(demo) restart-$(demo) build-$(demo) install-$(demo) update-bundle-$(demo) shell-$(demo) logs-$(demo) test-$(demo) test-coverage-$(demo) verify-$(demo))
.PHONY: up down restart build install update-bundle test shell logs test-coverage verify

help:
	@echo "Password Strength Bundle - Demo Commands"
	@echo ""
	@echo "  up-symfony8        Start Symfony 8 demo (FrankenPHP)"
	@echo "  down-symfony8      Stop demo containers"
	@echo "  test-symfony8      Smoke checks"
	@echo "  release-check      Pre-release HTTP smoke (up → verify → down)"
	@echo ""
	@echo "Shortcuts (default demo: symfony8):"
	@echo "  make up            Same as up-symfony8"
	@echo "  make down          Same as down-symfony8"
	@echo ""
	@echo "Access: http://localhost:$(DEFAULT_PORT) (override PORT in demo/symfony8/.env)"
	@echo ""

define up-target-template
up-$(1):
	@$(MAKE) -C $(1) up
endef

define down-target-template
down-$(1):
	@$(MAKE) -C $(1) down
endef

define restart-target-template
restart-$(1):
	@$(MAKE) -C $(1) restart
endef

define build-target-template
build-$(1):
	@$(MAKE) -C $(1) build
endef

define install-target-template
install-$(1):
	@$(MAKE) -C $(1) install
endef

define update-bundle-target-template
update-bundle-$(1):
	@$(MAKE) -C $(1) update-bundle
endef

define shell-target-template
shell-$(1):
	@$(MAKE) -C $(1) shell
endef

define logs-target-template
logs-$(1):
	@cd $(1) && docker-compose logs -f
endef

define test-target-template
test-$(1):
	@$(MAKE) -C $(1) test
endef

define test-coverage-target-template
test-coverage-$(1):
	@$(MAKE) -C $(1) test-coverage
endef

define verify-target-template
verify-$(1):
	@$(MAKE) -C $(1) ensure-up
	@port=$$(grep "^PORT=" $(1)/.env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$port" ] && port=$(DEFAULT_PORT); \
	code=000; \
	for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		code=$$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:$$port/en/" 2>/dev/null || echo "000"); \
		echo "$$code" | grep -qE '^[23]' && break; \
		sleep 2; \
	done; \
	if echo "$$code" | grep -qE '^[23]'; then echo "✅ $(1) OK (HTTP $$code)"; else echo "❌ $(1) HTTP $$code"; exit 1; fi
endef

$(foreach demo,$(DEMOS),$(eval $(call up-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call down-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call restart-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call build-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call install-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call update-bundle-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call shell-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call logs-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call test-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call test-coverage-target-template,$(demo))))
$(foreach demo,$(DEMOS),$(eval $(call verify-target-template,$(demo))))

test-all:
	@$(foreach demo,$(DEMOS),$(MAKE) test-$(demo);)

up: up-symfony8
down: down-symfony8
restart: restart-symfony8
build: build-symfony8
install: install-symfony8
update-bundle: update-bundle-symfony8
test: test-symfony8
test-coverage: test-coverage-symfony8
shell: shell-symfony8
logs: logs-symfony8
verify: verify-symfony8

release-verify:
	@for demo in $(DEMOS); do \
		port=$$(grep '^PORT=' $$demo/.env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
		[ -z "$$port" ] && port=$(DEFAULT_PORT); \
		echo "Release verify: $$demo (port $$port)..."; \
		$(MAKE) up-$$demo; \
		$(MAKE) -C $$demo update-bundle; \
		code=000; \
		for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
			code=$$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:$$port/en/" 2>/dev/null || echo "000"); \
			echo "$$code" | grep -qE '^[23]' && break; \
			echo "  waiting... $$i/15 (HTTP $$code)"; \
			sleep 2; \
		done; \
		$(MAKE) down-$$demo 2>/dev/null || true; \
		if ! echo "$$code" | grep -qE '^[23]'; then echo "$$demo healthcheck failed (HTTP $$code)"; exit 1; fi; \
		echo "OK $$demo (HTTP $$code)"; \
	done
	@echo "All demos passed release-verify."

release-check: release-verify
	@echo "All demos passed release-check."

clean:
	@rm -rf $(foreach demo,$(DEMOS),$(demo)/vendor $(demo)/var $(demo)/.phpunit.cache)
	@echo "✅ Cleaned vendor and cache from all demos."

BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/..)
include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-aggregate-update-deps.mk
