<?php namespace Vanderbilt\CensusExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class CensusExternalModule extends AbstractExternalModule
{
    function hook_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id) {
		$this->addScript($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id);
	}

    function hook_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id) {
		$this->addScript($project_id, $record, $instrument, $event_id, $group_id);
	}

        function addScript($project_id, $record, $instrument, $event_id, $group_id, $survey_hash = null, $response_id = null) {
            $module_data = ExternalModules::getProjectSettingsAsArray(array("vanderbilt_census_geocoder"), $project_id);
		if ($project_id && ($instrument == $module_data["instrument"]['value'])) {
			$addressField = $module_data['address']['value'];
			$keys = $module_data['keys']['value'];
			if (!is_array($keys)) {
				$keys = array($keys);
			}
			$fields = $module_data['fields']['value'];
			if (!is_array($fields)) {
				$fields = array($fields);
			}

			if (count($keys) == count($fields)) {
				echo "<script>
                $(document).ready(function() {
                    console.log('Census Geocoder loaded');
					function downloadCensusData() {
						var address = $('[name=\"".$addressField."\"]').val();
						var keys = ".json_encode($keys).";
						var fields = ".json_encode($fields).";

						if (address) {
							var encodedAddress = address.replace(/\s+/g, '+');
							encodedAddress = encodedAddress.replace(/United States/g, '+');
                            console.log('Looking up '+encodedAddress);
                            $.post('".APP_PATH_WEBROOT_FULL."/modules/vanderbilt_census_geocoder_v1.1/getAddress.php', { 'get':'address='+encodedAddress+'&benchmark=Public_AR_Census2010&vintage=Census2010_Census2010&layers=14&format=json' }, function(json) {
                                console.log('Got data from TigerWeb');
                                var data = JSON.parse(json);
							    if (data && data['result'] && data['result']['addressMatches'] && data['result']['addressMatches'][0] && data['result']['addressMatches'][0]['geographies'] && data['result']['addressMatches'][0]['geographies']['Census Blocks']) {
                                    console.log('TigerWeb lookup data present');
								    var lookupTable = data['result']['addressMatches'][0]['geographies']['Census Blocks'][0];
								    for (var i=0; i < fields.length; i++) {
									    if (lookupTable[keys[i]]) {
                                            console.log('Census Data Setting '+fields[i]+' to '+lookupTable[keys[i]]);
										    $('[name=\"'+fields[i]+'\"]').val(lookupTable[keys[i]]);
									    } else {
                                            console.log('Setting '+fields[i]+' to \"\"');
										    $('[name=\"'+fields[i]+'\"]').val('');
									    }
										$('[name=\"'+fields[i]+'\"]').change();
								    }
							    }
                            });
						}
					}
					$('[name=\"".$addressField."\"]').blur(function() {
                        console.log('Looking up Census data');
						downloadCensusData();
					});
                });
				</script>";
			}
		}
	}
}

