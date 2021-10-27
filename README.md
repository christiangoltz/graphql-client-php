# GraphQL Client for PHP

A client made for testing and consuming GraphQL endpoints

Targeting Symfony and Laravel GraphQL implementations


## Installation

``` composer require goltzchristian/graphql-client-php ```

## Setup

#### Laravel

``` 
use GraphQLClient\Client;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{
  /** @var Client */
  protected $graphql;
  
  public function setUp()
  {
    parent::setUp();
    $this->graphql = new LaravelTestGraphQLClient(
      $this->app,
      '/graphql'
    );
  }
  
}
```

#### Symfony

```
class RegistrationTest extends TestCase
{
  /** @var Client */
  protected $graphql;
  
  public function setUp()
  {
    parent::setUp();
    $this->graphql = new \GraphQLClient\SymfonyWebTestGraphQLClient(
      new Symfony\Bundle\FrameworkBundle\Client,
      '/graphql'  // change it to your endpoint's URI
    );
  }
}
```

## Examples

#### Mutations

```
public function testRegistrationSuccess()
{
  $params = [
    'secret' => 'mySecret',
    'password' => $this->getFaker()->password()
    'email' => $this->getFaker()->email()
  ];  // build a query
  $query = new Query('registerUser', $params, [
    new Field('id'),
    new Field('email'),
    new Field('id'),
  ]);  // execute the query
  $fields = $this->graphql->mutate($query)->getData();  // check if the server returned values for all requested fields
  $this->graphql->assertGraphQlFields($fields, $query);  // check if the user was created in the db
  $user = User::query()->where([
    'email' => $params['email'],
  ])->first();
  $this->assertNotNull($user);
}

```

#### Queries

```

public function testReadProfile()
{
  // build the query
  $query = new Query('viewer', [ 'token' => 'myToken', [
    new Field('profile', [
      new Field('id'),
      new Field('email'),
      new Query('posts', [ 'first' => 10 ], [
        new Field('edges', [
          new Field('node', [
            new Field('id'),
            new Field('content')
          ])
        ])
      ])
    ])
  ]);  // execute the query
  $fields = $this->graphql->query($query)->getData();  // check if the server returned all requested fields
  $this->assertGraphQlFields($fields, $query);  // check some simple field
  $this->assertEquals(
    $this->getUser()->getId(),
    $result['profile']['id']
  );  // retrieve post ids from response
  $postIds = array_map(function(array $item) {
    return $item['node']['id'];
  }, $fields['profile']['posts']['edges']);  // check if all user posts were contained in the response
  foreach ($this->getUser()->getPosts() as $post) {
    $this->assertNotFalse(array_search($post->getId(), $postIds));
  }
  
```

You can also checkout this [link](https://medium.com/@goltzchristian/testing-your-graphql-backend-in-php-41a2530ea556 "link") for detailed instructions
