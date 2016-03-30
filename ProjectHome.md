This project consists of a class which gathers and returns package details and location scans based on a specified postal carrier and tracking number. Tracking number auto-detection is available for most of the implemented carriers. The class is capable of returning data as an associative array, RSS feed, or JSON data.

This library uses the strategy pattern which allows for the easy interchanging of carriers and algorithms as requirements change (without impacting the library's external interface). An AbstractCarrier base class provides the foundation for each of the concrete carriers to extend. Implemented carriers include:

  * DHL
  * DHL Germany
  * FedEx
  * FedEx SmartPost
  * UPS
  * USPS

Also included in the project is a front controller which implements a simple, cache-aware, RSS tracking gateway. This is currently in use with the [Chumby Package Tracker widget](http://www.chumby.com/guide/widget/Package%20Tracker).