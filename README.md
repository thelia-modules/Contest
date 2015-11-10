# Contest

This module allow you to create basic QCM contest.

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Soldras/Contest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Soldras/Contest/?branch=master)

## Usage

Go to the tool menu and  click on "Contest".

### Create a Game

You have to the possibility to add a game wihout indexing it, just uncheck visible option

### Check Users

On list game view you can click on user icon of a game to check users participant.
You can generate a random winner too.

## Configuration

### WIN OPTION

Allow you to have page win/fail or joint participation page. ( default : true )

### CONNECT OPTION

Allow you to restric contest to connected user. ( default : false )

### MAX PARTICIPATION OPTION

Allow you to restrict participation for an email. ( default : 1 )

### FRIEND OPTION

Allow you to activate friend invitation. ( default : false )

### FRIEND MAX OPTION 

Limit number of input email are provide for sending email. ( default : 5 )

## Loop

### Game

#### Input arguments

|Argument           |Description                                                                                    |
|---                |---                                                                                            |
|**id**             | filter by id                                                                                  |
|**visible**        | filter by visible                                                                             |
|**title**          | filter by title                                                                               |
|**order**          | order result by "id","id-reverse","visible","visible-reverse","title","title-reverse", "description","description-reverse" |

#### Output Arguments


|Variable           |Description                                                |
|---                |---                                                        |
|**ID**             | id                                                        |
|**VISIBLE**        | (boolean) visible                                         |
|**TITLE**          | Game's title                                              |
|**DESCRIPTION**    | Game's description                                        |

### Question

#### Input arguments

|Argument           |Description                                                                                    |
|---                |---                                                                                            |
|**id**             | filter by id                                                                                  |
|**visible**        | filter by visible                                                                             |
|**title**          | filter by title                                                                               |
|**game_id**        | filter by game id                                                                               |
|**order**          | order result by "id","id-reverse","visible","visible-reverse","title","title-reverse", "description","description-reverse", "game_id","game_id-reverse" |

#### Output Arguments


|Variable           |Description                                                |
|---                |---                                                        |
|**ID**             | id                                                        |
|**VISIBLE**        | (boolean) visible                                         |
|**TITLE**          | Question's title                                          |
|**DESCRIPTION**    | Question's description                                    |
|**GAME_ID**        | Game's id                                                 |

### Answer

#### Input arguments

|Argument           |Description                                                                                    |
|---                |---                                                                                            |
|**id**             | filter by id                                                                                  |
|**visible**        | filter by visible                                                                             |
|**correct**        | filter by correct                                                                             |
|**title**          | filter by title                                                                               |
|**question_id**    | filter by question id                                                                         |
|**order**          | order result by "id","id-reverse","visible","visible-reverse","title","title-reverse", "description","description-reverse", "question_id","question_id-reverse","correct","correct-reverse" |

#### Output Arguments


|Variable           |Description                                                |
|---                |---                                                        |
|**ID**             | id                                                        |
|**VISIBLE**        | (boolean) visible                                         |
|**CORRECT**        | (boolean) correct                                         |
|**TITLE**          | Answer's title                                            |
|**DESCRIPTION**    | Answer's description                                      |
|**QUESTION_ID**    | Question's id                                             |

### Participate

#### Input arguments

|Argument           |Description                                                                                    |
|---                |---                                                                                            |
|**id**             | filter by id                                                                                  |
|**email**          | filter by email                                                                               |
|**win**            | filter by victory                                                                             |
|**game_id**        | filter by game_id                                                                             |
|**customer_id**    | filter by customer_id                                                                         |
|**order**          | order result by "id","id-reverse","email","email-reverse","victory","victory-reverse", "game_id","game_id-reverse", "customer_id","customer_id-reverse" |

#### Output Arguments


|Variable           |Description                                                |
|---                |---                                                        |
|**ID**             | id                                                        |
|**EMAIL**          | email                                                     |
|**WIN**            | (boolean) victory                                         |
|**GAME_ID**        | Game's id                                                 |
|**CUSTOMER_ID**    | Customer's id                                             |

