# Event Log

## Pipeline

```mermaid
flowchart LR
    Event(("Event<br/>dispatched"))
    --> Dispatcher["Dispatcher<br/><i>decorator</i>"]
    --> RecordEvent["RecordEvent<br/><i>action</i>"]
    --> Log[("Log")]
    --> RecordDestination["RecordDestination<br/><i>action</i>"]
    --> Destination[("Destination")]
    --> RecordDeliveries["RecordDeliveries<br/><i>action · uses DestinationProcessor</i>"]
    --> RecordDelivery["RecordDelivery<br/><i>action</i>"]
    --> Delivery[("Delivery")]
    --> ProcessDelivery["ProcessDelivery<br/><i>action · uses DeliveryProcessor</i>"]
    --> Attempt[("Attempt")]
```

### Contracts

```mermaid
classDiagram
    direction LR

    class Recordable {
        <<interface>>
    }
    class ForEntity {
        <<interface>>
        +Entity entity
        +broadcastAs() string
    }
    class Destinationable {
        <<interface>>
    }
    class Webhookable {
        <<interface>>
    }
    class Provider {
        <<interface>>
        +string destinationProcessor
        +string deliveryProcessor
    }
    class DestinationProcessor {
        <<interface>>
    }
    class DeliveryProcessor {
        <<interface>>
    }
    class Manager {
        +register(destinationable, provider)
        +getProvider(destinationable) Provider
    }

    ForEntity <|-- Recordable
    Recordable <|-- Destinationable
    Destinationable <|-- Webhookable

    Provider ..> DestinationProcessor : references
    Provider ..> DeliveryProcessor : references
    Manager ..> Provider : resolves
```
