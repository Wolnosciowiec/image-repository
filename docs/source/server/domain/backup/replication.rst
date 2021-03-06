Data replication
================

File Repository does not replicate the data on application level as it does not make sense to do so.
There are specialized filesystems such as S3, Glusterfs or DRBD, specialized database servers that handles primary-replica and primary-primary replication.

When setting up the replication you need to remember, that the application itself cannot keep any state locally, that's the rule of the replication.
Often people forget about the application cache, which is not so obvious.

**There are 3 areas that needs to be replicated, so the application could be scaled:**

- Storage backend
- Database
- Cache

Choosing scalable storage backends for File Repository
------------------------------------------------------

- Min.io (using S3 adapter)
- Amazon S3 (using S3 adapter)
- GlusterFS (local filesystem)
- DRBD (local filesystem)
- Ceph (local filesystem)


Selecting a scalable database
-----------------------------

Any modern database server supports the replication, it's up to you to pick the best. At RiotKit we are preferring PostgreSQL.
Please note that SQLite3 is a tiny scale in-file database that does not scale.

- PostgreSQL [Officially supported by File Repository, suggested]
- MySQL [Officially supported by File Repository]
- Oracle [Supported by libraries, not well tested]
- Microsoft SQL Server [Supported by libraries, not well tested]


Using a scalable cache
----------------------

Cache driver cannot be relying on local filesystem, needs to be distributed, we suggest to use one of those:

- Redis
- Memcached


Both are supported by Symfony and by File Repository. Officially we run automatic at least on Redis on our CI.
