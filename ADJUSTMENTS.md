- What happens when either event_log or destinatino create fails since it happens synchronously
- We need a test + PHPStan Rule + Rector rule to ensure `SerializesModels` does not get added to `Trigger`
    - Then we need to update the readme with a note as to why it's not provided by default exactly like `actions` does
- We need to strip all relationships from a `Model` before storing the event in the database somehow
- All of actions should have a seed of a single record, as such we should use that single record to provide a `ShouldBeUnique & uniqueId()` to protect against race condtions and duplicate data.
- We will NOT dispatch `RecordEvent`, instead we will `->now()`
- We will not dispatch `RecordDestination`, instead we will `->now()`
- `EventLog` needs a `status` column that is a `state-machine` with the states of `ready`, `pending`, `processing`, `completed`, `failed`
    - `ready` is the default state the record should be creatd with, we can set this value on the model
- Each Action node in the full flow will be responsible for it's operation and kicking off the next `Action` in the flow.
    - The first operation of an `Action` is to move the record from `pending` to `processing`
    - The second operation of an `Action` will be to conduct it's operations
    - The third operation is to move the current record to either `completed` or `failed`
        - The fourth operation is to kick off the next `Action` in the flow if the third operation was successful
    - When the next `Action` kickoff is complete the current `Action` should move the current record from `ready` to `pending`
- We are going to have reconciliation schedules jobs running to ensure we haven't missed any records
// This means it's technically possible that a job is aready on the queue at the the time our
// reconciliation process runs. We need to ensure that we don't end up with duplicate records in this case.

// Every Model needs a state machine to know which have made it to the next stage
// state machine should included a pending, processing, completed, failed
// Our job is always is always pending state

// We should store the destinationables in the database so that code changes do not impact pending jobs

// We are not relying only on reconciliation the happy path should be one batch
// This means that Destination for example will dispatch something to the queue, IF that is successful we immediately transition
// from pending to processign

// We need idempotency when creating delivery records
// We will be looking or all destinations that are processing, the only way
// that moves to completed is if all delivery records were created successfully
// If any fail, it wouldn't transition and would be picked up on the next scheduled run


// Reconciliation query needs a lookback
// Schedule job needs a config
// The look back and the schedule occurrence need to match, so ideally we are able to set a config of minutes
// that both reference
