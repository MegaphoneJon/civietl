<?php
$clientName = 'uaf';
require_once "$clientName.settings.local.php";
$GLOBALS['logFolder'] = "$workroot/errors";
$GLOBALS['workroot'] = $workroot;
$importDefaults = [
  'project_name' => 'Uaf',
  'reader_type' => 'CsvReader',
  'cache_type' => 'ArrayCache',
  'writer_type' => 'CiviCrmApi4',
];
$importSettings = [
  'StepOne' => [
    'readerOptions' => [
      // required for CsvReader: file_path, data_primary_key.
      'file_path' => "$workroot/raw data/full_archive/constituents.csv",
      'data_primary_key' => 'LGL Constituent ID',
    ],
    'writerOptions' => [
      // required for CsvWriter/JsonWriter: file_path.
      'file_path' => "$workroot/data/contacts.csv",
      // required for CiviCrmApi4: civi_primary_entity.
      'civi_primary_entity' => 'Contact',
      'match_fields' => ['external_identifier'],
    ],
  ],
  'ContactSplit' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/constituents.csv",
      'data_primary_key' => 'LGL Constituent ID',
    ],
    'writerOptions' => [
      'file_path' => "$workroot/data/contacts2.csv",
      'civi_primary_entity' => 'Contact',
    ],
  ],
  'ContactSubtypes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/contact_subtypes.csv",
      'data_primary_key' => 'Contact Type ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'ContactType',
      'match_fields' => ['name'],
    ],
  ],
  'GrantMakers' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/grant_making_institutions.csv",
      'data_primary_key' => 'external_identifier',
    ],
    'writerOptions' => [
      'file_path' => "$workroot/data/tempGM.csv",
      'civi_primary_entity' => 'Contact',
    ],
  ],
  'CommunicationPreferences' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/constituents.csv",
      'data_primary_key' => 'LGL Constituent ID',
    ],
    'writerOptions' => [
      'file_path' => "$workroot/data/contacts.csv",
      'civi_primary_entity' => 'Contact',
    ],
  ],
  // 'Salutations' => [
  //   'readerOptions' => [
  //     'file_path' => "$workroot/raw data/full_archive/constituents.csv",
  //     'data_primary_key' => 'LGL Constituent ID',
  //   ],
  //   'writerOptions' => [
  //     'file_path' => "$workroot/data/contacts.csv",
  //     'civi_primary_entity' => 'Contact',
  //   ],
  // ],
  'Websites' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/websites.csv",
      'data_primary_key' => 'LGL Website ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Website',
    ],
  ],
  'Emails' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/emails.csv",
      'data_primary_key' => 'LGL Email ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Email',
    ],
  ],
  'Phones' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/phone_numbers.csv",
      'data_primary_key' => 'LGL Phone ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Phone',
    ],
  ],
  'Addresses' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/street_addresses.csv",
      'data_primary_key' => 'LGL Address ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Address',
    ],
  ],
  'FixedAddresses' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/Bad_Addresses FOR IMPORT gail corrected errors.csv",
      'data_primary_key' => 'LGL Address ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Address',
    ],
  ],
  'Notes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/notes.csv",
      'data_primary_key' => 'LGL Note ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Note',
    ],
  ],
  'Groups' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/constituents.csv",
      'data_primary_key' => 'LGL Constituent ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'GroupContact',
    ],
  ],
  'FinancialTypes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/funds.csv",
      'data_primary_key' => 'LGL Fund ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'FinancialType',
      'match_fields' => ['name'],
    ],
  ],
  // 'FTSnapshot' => [
  //   'writerOptions' => [
  //     'filename' => "$workroot/data/ftdump.sql"
  //   ]
  // ]
  'OptionGroups' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/optionGroups.csv",
      'data_primary_key' => 'Option Group ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'OptionGroup',
      'match_fields' => ['name'],
    ],
  ],
  'OptionValues' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/optionValues.csv",
      'data_primary_key' => 'Option Values ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'OptionValue',
      'match_fields' => ['name'],
    ],
  ],
  'CustomGroups' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/customGroups.csv",
      'data_primary_key' => 'Custom Group ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'CustomGroup',
      'match_fields' => ['name'],
    ],
  ],
  'CustomFields' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/customFields.csv",
      'data_primary_key' => 'Custom Field ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'CustomField',
      'match_fields' => ['name'],
    ],
  ],
  'Campaigns' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/campaigns.csv",
      'data_primary_key' => 'LGL Campaign ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Campaign',
      'match_fields' => ['external_identifier'],
    ],
  ],
  'Appeals' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/appeals.csv",
      'data_primary_key' => 'LGL Appeal ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Campaign',
      'match_fields' => ['external_identifier'],
    ],
  ],
  'Contributions' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_gifts.csv",
      'data_primary_key' => 'LGL Gift ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Contribution',
      'file_path' => "$workroot/data/tempCon.csv",
      'match_fields' => ['Legacy_Contribution_Data.LGL_Gift_ID'],
    ],
  ],
  'ContributionsThirdParty' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_gifts.csv",
      'data_primary_key' => 'LGL Gift ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Contribution',
      'file_path' => "$workroot/data/tempCon3.csv",
      'match_fields' => ['Legacy_Contribution_Data.LGL_Gift_ID'],
    ],
  ],

  'ContributionsMatching' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_matching_gifts.csv",
      'data_primary_key' => 'LGL Gift ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Contribution',
      'file_path' => "$workroot/data/tempConMatch.csv",
      'match_fields' => ['Legacy_Contribution_Data.LGL_Gift_ID'],
    ],
  ],
  'ContributionsMatchingFlip' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_matching_gifts.csv",
      'data_primary_key' => 'LGL Gift ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'ContributionSoft',
      'file_path' => "$workroot/data/tempConMatch.csv",
      'match_fields' => ['Legacy_Contribution_Data.LGL_Gift_ID'],
    ],
  ],
  'AutoMatchingGift' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_matching_gifts.csv",
      'data_primary_key' => 'LGL Gift ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Contribution',
      'file_path' => "$workroot/data/tempAutoMatch.csv",
      'match_fields' => ['Legacy_Contribution_Data.LGL_Gift_ID'],
    ],
    // 'writer_type' => 'CsvWriter',
  ],
  'MatchingGiftOrganizations' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_matching_gifts.csv",
      'data_primary_key' => 'LGL Constituent ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'GroupContact',
      'file_path' => "$workroot/data/tempAutoMatchOrgs.csv",
      'match_fields' => ['contact_id'],
    ],
  ],
  'ContributionsInKind' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_in_kind.csv",
      'data_primary_key' => 'LGL Gift ID',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Contribution',
      'file_path' => "$workroot/data/tempConKind.csv",
      'match_fields' => ['Legacy_Contribution_Data.LGL_Gift_ID'],
    ],
  ],
  'SoftCredits' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_soft_credits.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'ContributionSoft',
      'file_path' => "$workroot/data/tempSC.csv",
    ],
  ],
  'SoftCreditsInHonor' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_in_honor_of.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'ContributionSoft',
      'file_path' => "$workroot/data/tempSCH.csv",
    ],
  ],
  'SoftCreditsInMemory' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_in_memory_of.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'ContributionSoft',
      'file_path' => "$workroot/data/tempSCM.csv",
    ],
  ],
  'ContributionNotes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_gifts.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Note',
      'file_path' => "$workroot/data/tempConNotes.csv",
    ],
  ],
  'ContributionsInKindNotes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_in_kind.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Note',
      'file_path' => "$workroot/data/tempConNotes.csv",
    ],
  ],
  'SoftCreditNotes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_soft_credits.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Note',
      'file_path' => "$workroot/data/tempSCNotes.csv",
    ],
  ],
  'ContributionsMatchingNotes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/gift_matching_gifts.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Note',
      'file_path' => "$workroot/data/tempCMNotes.csv",
    ],
  ],
  'RelationshipTypes' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/relationshipTypes.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'RelationshipType',
      // This allows me to update existing records by matching on these fields.
      'match_fields' => ['name_a_b', 'name_b_a'],
    ],
  ],
  'Relationships' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/constituent_relationships.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Relationship',
    ],
  ],
  'SplitContactsRelationships' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/constituents.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Relationship',
    ],
  ],
  'MemberRelationships' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/memberships.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Relationship',
    ],
  ],
  'Attachments' => [
    'readerOptions' => [
      'file_path' => "$workroot/raw data/full_archive/documents.csv",
      'data_primary_key' => '',
    ],
    'writerOptions' => [
      'civi_primary_entity' => 'Attachment',
    ],
    'writer_type' => 'CiviCrmApi3',
  ],
];
