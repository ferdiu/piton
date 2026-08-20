## Findings from the Laravel 13 upgrade (2026-08)

### Bugs surfaced by the new test suite (remaining; tests skipped with comments)

- `Instances::removeUselessInsts()` — passes dataset keys to `array_splice()` as
  zero-based offsets, deleting the wrong rows (requires a logic decision: map keys
  to offsets or iterate differently)
  (see `tests/Unit/InstancesUnitTest.php::test_remove_useless_instances_drops_missing_classes`).
- `RuleBasedModel::saveToDB()` — indexes a `valuesSql` array assuming 29 entries
  while test-measures provide 27 (requires a logic decision on the intended mapping)
  (`tests/Feature/ModelPersistenceTest.php::test_trained_model_round_trips_through_database`).

### Security backlog (pre-existing, from security review of the upgrade)

- `DiscriminativeModel.php:107` and `RuleBasedModel.php:205` — `unserialize()` on
  database content: write access to those tables means PHP object injection.
  Consider JSON or `unserialize($data, ['allowed_classes' => [...]])`.
- `DBFit.php:1393` — `eval()` on a domain-array string; audit how the string is built.
- Raw SQL built by string interpolation in `Utils.php` mysql_* helpers and `DBFit`
  (custom quoting instead of prepared statements; see also item 9 above).

### Other findings

- `Console/CreateExample.php` reads `database.connections.mysql.*` while the package
  connection is `piton_connection` — confusing, may fail for consumers without a
  `mysql` connection configured.
- `ModelVersion::problem()` Eloquent relation is missing (needed if feature tests
  should access `$version->problem` directly).
- Measure code coverage in CI (pcov is installed there); locally a coverage driver
  (pcov or xdebug) is required for `composer test-coverage`.

## Original TODO

- Separate DBFit into 3 classes: fetcher, fitter and predicter. (The old extension-less
  draft files were deleted during the Laravel 13 upgrade; DBFit.php is still monolithic.)
- Associare ai modelli SOLO gli attributi associati alle regole e non tutti gli attributi su cui è stato effettuato il training. L’obiettivo è che se, ad esempio, per un’istanza l’attributo BMI = NULL, ma non ho regole per BMI associate a quel modello, posso comunque effettuare una predizione. Ancora meglio, si potrebbe trovare un modo per attivare comunque la regola se, ad esempio, BMI è presente a partire dalla N-esima regola ma non è presente fra le prime, e quindi associadno gli attributi addirittura alle regole stesse invece che non ai modelli.
- Trovare un modo per stampare dei messaggi di errore chiari in caso di config sbagliata. Si è tentato di farlo durante tutto lo sviluppo, ma pul certamente essere migliorato. Mantenere questa cosa anche in caso di nuovi parametri, e valutare se ciò che è presente al momento è sufficiente.
- Update the storage of rules into the piton_rules table in the database.
- Rename 'update_models' into 'learn_problem'


- Fix those == that should actually be === https://stackoverflow.com/questions/12151997/why-does-1234-1234-test-evaluate-to-true#comment16259587_12151997
- Make sql querying secure with addslashes or whatever

- Parallelize code ( https://medium.com/@rossbulat/true-php7-multi-threading-how-to-rebuild-php-and-use-pthreads-bed4243c0561 )
- Code is probably not ready for weighted datasets (e.g., see pushInstance)

- Add to each model only the attributes associated with its rules insted of all the attributes used for training.
- Use CheckCutOff to also check the number of the instances (atm it only checks for a minimum ratio between the two classes). Furthermore, find a better name for CutOffValue.
- One column can generate more attributes; make this part of code elegant.
- Add stemming in italian
- Add Bag-of-words parameter for specifying the language for each column
- Randomly shuffle train in an independent fashon (Not for JRip; e.g., add a "shuffle" parameter)
- Text processing via NLPTools http://php-nlp-tools.com/documentation/transformations.html http://php-nlp-tools.com/documentation/tokenizers.html
- Implement an unweighted version of Instances?
- Creation of "dead nodes" in the hierarchy in case a model isn't trained for a specific sub-problem, and of the "I couldn't predict" case in predictByIdentifier.
- Revise the json_logic_rules field in the piton_class_models table.
- Print all errors messages into a Laravel log file instead of stdin.
- Print clear error messages in case of errors in the configuration. Restore some form of logging to the control flow on multiple levels of detail.
- Build test suite for verifying the behavior of DBFit and learning algorithms

- provide method for creating arbitrary attributes
- provide method for creating column categorical from a checkbox+value pair of attrs, with reverse=false flag
- don't recurse when the outcome is false. ? Note: NO_thing is not entirely safe
- At prediction time just interrogate the database once and then use that same row for every level. Is there a way to avoid multiple queries?
- for ($j = 0; $j < $data->numInstances(); $j++) { che diventi un generatore di istanze
- PRip: check consistency between with Weka's JRip (must activate debug info)
- ? Ignore nan rules at predict time?
- Move processing from SQL to PHP? force non-null values check in code?
