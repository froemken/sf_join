# sfjoin

This is just a TYPO3 demo extension to show you possibilities on how to get records from database by TYPO3 API.

Please have a look into ProductRepository class. There are currently 4 main methods collecting records:

* by Extbase Query
* by TYPO3 QueryBuilder injected into Extbase Query
* by just TYPO3 QueryBuilder
* by getRecords() of ContentObjectRenderer

## Extbase Query

Use it as often and whereever possible.

PROS:
* It is short (less code)
* Workspace overlay integrated
* Language overlay integrated
* Translated records are respecting ORDER BY
* COUNT on JOINed tables is working. Extbase changes SELECT to `COUNT(DISTINCT table.uid)`

CONS:
* It's always `SELECT *`
* You can not change the SELECT part. Working with AVG(), SUBSTRING(), ... is not possible
* GROUP BY is not implemented
* There is no possibility to sort sub records

## TYPO3 QueryBuilder with Extbase Query

It's OK for single table usage.
Don't use it with JOINs because of the COUNT problem.

PROS:
* You can modify all query parts to your need
* Extendability: Send QueryBuilder to a Hook, Event or other method and you can change everything

CONS:
* COUNT on JOINed tables is not working. SELECT is still `COUNT()` instead of `COUNT(DISTINCT table.uid)`
* A JOIN produces a cartesian product
* On JOINed tables you have to add GROUP BY on your own
* If you're working with ONLY_FULL_GROUP_BY you have to add ALL columns of SELECT to GROUP BY.
* It's up to you to JOIN tables correctly. Do not forget to add tablenames, fieldname and all the other matching columns (from TCA)
* You have to add the storage PIDs on your own
* It's your job to modify your query for correct language handling

## Plain TYPO3 QueryBuilder

This is for TYPO3 Pros only. You really have to know what you're doing.

PROS:
* Do whatever you want
* COUNT() is always right, as it was NOT set by an automatism

CONS:
* The resultset is not compatible with QueryResultInterface. So, you're working with the already fetched records
* You have to call versionOL()
* You have to call getLanguageOverlay()
* If you want sorted and translated records you have to add a really huge extra query on your own
* You have to map your resultset to domain models on your own
* You have to work with your own Pagination/Paginator or you have to work with full resultset as array
* You have to add the storage PIDs on your own
* A method to collect JOINed query can be a lot of work and very very long

## getRecords of cObj

Very easy. The Typo3DatabaseProcessor of fluid package also uses this method. Translation and Workspacing will be done for you.

PROS:
* Easy
* Syntax is known since ages
* You can modify SELECT part
* You can work with JOINs
* GROUP BY is also possible

CONS:
* Remember special syntax for column identifier: `{#column}`
* Multiple JOINs can be very messy
* As table alias is not possible, you always have to use full tablename: `tx_sfjoin_domain_model_product.title` instead of `p.title`
* Fixed collection of allowed SELECT functions: COUNT|MAX|MIN|AVG|SUM|DISTINCT
* ORDER BY on translated records is not possible. Records are sorted in default language. Records were translated 1:1 not respecting ORDER BY.
