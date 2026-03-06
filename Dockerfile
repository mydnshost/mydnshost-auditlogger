FROM registry.shanemcc.net/mydnshost-public/api-base AS api

FROM registry.shanemcc.net/mydnshost-public/api-docker-base:latest
MAINTAINER Shane Mc Cormack <dataforce@dataforce.org.uk>

COPY --from=api /dnsapi /dnsapi

COPY . /dnsapi/auditlogger

ENTRYPOINT ["/dnsapi/auditlogger/AuditLogger.php"]
