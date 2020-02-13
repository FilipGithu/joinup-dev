@api @group-a
Feature: SEO for news articles.
  As an owner of the website
  in order for my news to be better visible on the web
  I need proper metatag to be encapsulated in the html code.

  Scenario Outline: Basic metatags are attached as JSON schema on the page.
    Given collections:
      | title                       | state     |
      | Joinup SEO event collection | validated |
    And users:
      | Username          | E-mail                 | First name | Family name |
      | Joinup SEO author | joinup.seo@example.com | Patrick    | Stewart     |
    And "event" content:
      | title            | short title   | web url   | start date                      | end date                        | body                                               | logo     | agenda        | location                           | organisation        | scope         | keywords | collection                  | state     |
      | Joinup SEO event | JOINUPSEO2020 | <web url> | Wed, 25 Dec 2019 13:00:00 +0100 | Wed, 01 Jan 2020 13:00:00 +0100 | summary: Summary of event. - value: Body of event. | logo.png | Event agenda. | Rue Belliard 28, Brussels, Belgium | European Commission | International | Alphabet | Joinup SEO event collection | validated |

    When I visit the "Joinup SEO event" event
    Then the metatag JSON should be attached in the page
    And 1 metatag graph of type "Event" should exist in the page
    And the metatag graph of the item with "name" "Joinup SEO event" should have the following properties:
      | property    | value                    |
      | @type       | Event                    |
      | name        | Joinup SEO event         |
      # Though it would be nice to have this to the Joinup URL, Search Engines expect this to be the URL of the event.
      # If a website is provided, then that means that the entity in Joinup is simply a promotion, and all the handling
      # of registrations etc, already has a website, thus we point to that location.
      | url         | <expected url>           |
      # Summary is preferred over the body of the entity.
      | description | Summary of event.        |
      | startDate   | 2019-12-25T15:00:00+0100 |
      | endDate     | 2020-01-01T15:00:00+0100 |
      # $base_url$ will be replaced with the base url of the website.
      | @id         | <expected url>           |
    And the metatag graph of the item with "name" "Joinup SEO event" should have the following "image" properties:
      | property             | value       |
      | @type                | ImageObject |
      | representativeOfPage | True        |
      # The test files are assigned a random name so it is very hard to assert the real url. However, when the file is
      # found and added to the page, the dimensions are added as well. Asserting the dimensions also asserts the url in
      # a way.
      | width                | 377         |
      | height               | 139         |
    And the metatag graph of the item with "name" "Joinup SEO event" should have the following "location" properties:
      | property | value           |
      | @type    | Place           |
      | name     | Rue Belliard 28 |
    # Target the location subgraph which has the name property set to "Rue Belliard 28".
    And the metatag subgraph of the item with "name" "Rue Belliard 28" should have the following "address" properties:
      | property      | value           |
      | @type         | PostalAddress   |
      | streetAddress | Rue Belliard 28 |
    And the metatag subgraph of the item with "name" "Rue Belliard 28" should have the following "geo" properties:
      | property  | value          |
      | @type     | GeoCoordinates |
      # Geo coordinates are hardcoded as offered by the service.
      | latitude  | 45.82372       |
      | longitude | 6.55121        |

    When I click "Keep up to date"
    Then I should see the "Joinup SEO event" tile
    # No metatags are defined for the keep up to date page.
    # No metatags JSON in general means also that the entity metatags of the news item
    # is also not attached when the tile is present.
    And the metatag JSON should not be attached in the page

    Examples:
      | web url                                       | expected url                                                             |
      |                                               | $base_url$/collection/joinup-seo-event-collection/event/joinup-seo-event |
      # Urls need a title value in the 0 index and a url in the 1 index of the value to work, otherwise it is parsed
      # wrongly.
      # @see: \Drupal\Driver\Fields\Drupal8\LinkHandler::expand
      | 0: Some url - 1: http://some-random-event-url | http://some-random-event-url                                             |
