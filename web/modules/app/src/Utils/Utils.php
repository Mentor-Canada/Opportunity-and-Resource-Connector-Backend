<?php

namespace Drupal\app\Utils;

use Drupal\app\Factories\NodeFactory;
use Drupal\app\Factories\UserFactory;
use Drupal\app_inquiry\Inquiry;
use Drupal\node\Entity\Node;
use Goodby\CSV\Export\Standard\Exporter;
use Goodby\CSV\Export\Standard\ExporterConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Utils
{
    public static function getApplicantInfo($id)
    {
        $inquiry = new Inquiry($id);

        return [
            'firstName' => $inquiry->firstName,
            'lastName' => $inquiry->lastName,
            'email' => $inquiry->email
        ];
    }

    public static function geocode($address)
    {
        $geocoder = new \GoogleMapsGeocoder($address);
        $geocoder->setApiKey($_ENV['GOOGLE_API_KEY']);
        $r = $geocoder->geocode();
        return $r['results'][0];
    }

    public static function latLongDist($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        $pi = pi();
        $x = sin($latitudeFrom * $pi/180) *
      sin($latitudeTo * $pi/180) +
      cos($latitudeFrom * $pi/180) *
      cos($latitudeTo * $pi/180) *
      cos(($longitudeTo * $pi/180) - ($longitudeFrom * $pi/180));
        $x = atan((sqrt(1 - pow($x, 2))) / $x);
        return abs((1.852 * 60.0 * (($x/$pi) * 180)) / 1.609344);
    }

    public static function addDateFilter(&$q, &$params)
    {
        if (isset($_REQUEST['start'])) {
            $q.= " AND node_revision.revision_timestamp >= :start";
            $params[':start'] = $_REQUEST['start'];
        }
        if (isset($_REQUEST['end'])) {
            $q.= " AND node_revision.revision_timestamp <= :end";
            $params[':end'] = $_REQUEST['end'] + 24 * 60 * 60;
        }
    }

    public static function globalSettings($lang = "en", $country = "ca"): array
    {
        $query = \Drupal::entityQuery('node');
        $query->condition('status', 1);
        $query->condition('type', 'global_settings');
        $query->condition('field_gs_country', 'ca');
        $query->range(0, 1);
        $nids = $query->execute();
        if (!count($nids)) {
            return [];
        }
        $nid = current($nids);

        /* @var $node \Drupal\node\Entity\Node */
        $node = Node::load($nid);
        $languages = $node->getTranslationLanguages();
        if (isset($languages[$lang])) {
            $node = $node->getTranslation($lang);
        }

        $addressName = $node->get("field_gs_address_name")->getValue()[0]["value"];
        $addressLine1 = $node->get("field_gs_address_line_1")->getValue()[0]["value"];
        $addressLine2 = $node->get("field_gs_address_line_2")->getValue()[0]["value"];
        $addressLine3 = $node->get("field_gs_address_line_3")->getValue()[0]["value"];

        $phone = $node->get("field_gs_phone")->getValue()[0]["value"];
        $extension = $node->get("field_gs_extension")->getValue()[0]["value"];

        $phoneDisplay = "1 (";
        $phoneDisplay .= substr($phone, 0, 3);
        $phoneDisplay .= ") ";
        $phoneDisplay .= substr($phone, 3, 3);
        $phoneDisplay .= "-";
        $phoneDisplay .= substr($phone, 6, 4);

        $phoneDisplayWithExtension = $phoneDisplay;
        if ($extension) {
            $phoneDisplayWithExtension .= " ext. $extension";
        }

        $phoneLink = "tel:1$phone";
        if ($extension) {
            $phoneLink .= ",$extension";
        }

        $email = $node->get("field_gs_email")->getValue()[0]["value"];
        $emailDisplay = $email;
        $emailLink = "mailto:$email";

        $fb = $node->get("field_gs_facebook")->getValue()[0]["value"];
        $tw = $node->get("field_gs_twitter")->getValue()[0]["value"];
        $li = $node->get("field_gs_linkedin")->getValue()[0]["value"];
        $ig = $node->get("field_gs_instagram")->getValue()[0]["value"];
        $yt = $node->get("field_gs_youtube")->getValue()[0]["value"];
        $social = [
            "fb"  => $fb,
            "tw"  => $tw,
            "li"  => $li,
            "ig"  => $ig,
            "yt"  => $yt
        ];

        $globalSettings = [
            "addressName"               => $addressName,
            "addressLine1"              => $addressLine1,
            "addressLine2"              => $addressLine2,
            "addressLine3"              => $addressLine3,
            "phone"                     => $phone,
            "extension"                 => $extension,
            "phoneDisplay"              => $phoneDisplay,
            "phoneDisplayWithExtension" => $phoneDisplayWithExtension,
            "phoneLink"                 => $phoneLink,
            "email"                     => $email,
            "emailDisplay"              => $emailDisplay,
            "emailLink"                 => $emailLink,
            "social"                    => $social
        ];

        return $globalSettings;
    }

    public static function request($uri, $httpKernel): Response
    {
        $request = \Drupal::request();
        $postBody = $request->getContent();
        $sub_request = Request::create(
            $uri,
            'POST',
            [],
            [],
            [],
            [],
            $postBody
        );
        $sub_request->setRequestFormat('json');
        $sub_request->headers->set('content-type', 'application/json');
        return $httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
    }

    public static function getSubRequestData($uri, $httpKernel)
    {
        $sub_request = Request::create($uri, 'GET', $_GET);
        $subResponse = $httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST);
        $data = $subResponse->getContent();

        return json_decode($data, true);
    }

    public static function t($string)
    {
        try {
            return t($string);
        } catch (\Exception $e) {
            return $string;
        }
    }

    public static function readArray($theField, $isLocation = null)
    {
        $array = [];
        foreach ($theField as $key => $value) {
            if ($value) {
                if ($isLocation) {
                    $array[] = t($value['name']);
                } elseif ($value->entity) {
                    $array[] = $value->entity->label();
                } elseif (is_string($value)) {
                    $array[] = t($value);
                } elseif (get_class($value) == 'Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem') {
                    $array[] = $value->get('target_id')->getValue();
                } else {
                    print "unknown type";
                    exit;
                }
            }
        }
        return implode(", ", $array);
    }

    public static function exporter($rows, $fileName)
    {
        $config = new ExporterConfig();
        $exporter = new Exporter($config);

        header('Content-Encoding: UTF-8');
        header("Content-type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename=$fileName");
        header("Pragma: no-cache");
        header("Expires: 0");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        $exporter->export('php://output', $rows);
    }

    /**
     * @deprecated
     */
    public static function getCurrentNode($id)
    {
        return self::loadNodeByUUid($id);
    }

    public static function loadNodeByUUid($uuid)
    {
        $node = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['uuid' => $uuid]);
        return current($node);
    }

    public static function mapValues($values, $field)
    {
        $options = explode(",", $values);

        switch ($field) {
      case "field_types_of_mentoring":
        $map = [
            "5403" => "app-type-of-mentoring-1-to-1",
            "5402" => "app-type-of-mentoring-group",
            "5405" => "app-type-of-mentoring-team",
            "5401" => "app-type-of-mentoring-e-mentoring",
            "5404" => "app-type-of-mentoring-peer",
        ];
        break;

      case "field_primary_meeting_location":
        $map = [
            "5406" => "app-us-program-meeting-agency",
            "5407" => "app-us-program-meeting-community",
            "5408" => "app-us-program-meeting-faith",
            "5409" => "app-us-program-meeting-juvenile",
            "5410" => "app-us-program-meeting-mental",
            "5412" => "app-us-program-meeting-school",
            "5413" => "app-us-program-meeting-workplace",
            "5536" => "app-us-program-meeting-after-school",
            "5411" => "app-us-program-meeting-online",
            "5414" => "app-other",
        ];
        break;

      case "field_program_which_goals":
        $map = [
            "5415" => "app-us-career-exploration",
            "5416" => "app-us-education-academic-support",
            "5417" => "app-us-friendship-socialization",
            "5418" => "app-us-healthy-behaviours",
            "5419" => "app-us-job-placement-performance",
            "5420" => "app-us-reduce-recidivism",
            "5421" => "app-other",
        ];
        break;

      case "field_program_ages_served":
        $map = [
            "5428" => "app-us-7-and-under",
            "5429" => "app-us-8-10",
            "5430" => "app-us-11-14",
            "5431" => "app-us-15-18",
            "6152" => "app-us-19-24",
            "5432" => "app-other",
        ];
        break;

      case "field_program_family_served":
        $map = [
            "5433" => "app-us-foster-care",
            "5434" => "app-us-group-home",
            "5435" => "app-us-guardian",
            "5436" => "app-us-kinship-care",
            "5437" => "app-us-single-parent-family",
            "5438" => "app-us-two-parent-family",
            "5505" => "app-us-any-of-the-above",
            "5439" => "app-other",
        ];
        break;

      case "field_program_youth_served":
        $map = [
            "5507" => "app-us-academically-at-risk",
            "5508" => "app-us-college-post-secondary-student",
            "5510" => "app-us-foster-residential-or-kinship-care",
            "5444" => "app-us-gang-involved",
            "5512" => "app-us-gifted-talented-academic-achiever",
            "5448" => "app-us-incarcerated-parent",
            "5449" => "app-us-low-income",
            "6153" => "app-us-opportunity-youth",
            "5517" => "app-us-physical-disabilities-special-care-needs",
            "5518" => "app-us-recent-immigrant-refugee",
            "5520" => "app-us-single-parent-household",
            "5453" => "app-us-youth-with-disabilities",
            "5441" => "app-us-adjudicated-court-involved",
            "5509" => "app-us-first-generation-college",
            "5511" => "app-us-gang-at-risk",
            "5440" => "app-us-general-youth-population",
            "5513" => "app-us-homeless-runaway",
            "5514" => "app-us-lgbtq-youth",
            "5515" => "app-us-mental-health-issues",
            "5516" => "app-us-parent-involved-in-military",
            "5451" => "app-us-pregnant-parenting",
            "5519" => "app-us-school-drop-out",
            "5521" => "app-us-special-education",
            "5497" => "app-other",
        ];
        break;

      case "field_program_ages_mentor_target":
        $map = [
            "5457" => "app-us-age-under-18",
            "5458" => "app-us-age-18-24",
            "5459" => "app-us-age-25-34",
            "5460" => "app-us-age-35-49",
            "5461" => "app-us-age-50-65",
            "5462" => "app-us-age-over-65",
            "5463" => "app-other",
        ];
        break;

      case "field_program_operated_through":
        $map = [
            "5489" => "app-us-program-operated-business",
            "5490" => "app-us-program-operated-community",
            "5491" => "app-us-program-operated-faith",
            "5492" => "app-us-program-operated-government",
            "5493" => "app-us-program-operated-higher-education",
            "5494" => "app-us-program-operated-resident",
            "5495" => "app-us-program-operated-school",
            "5496" => "app-other",
        ];
        break;

      case "field_program_trains_mentors":
        $map = [
            "426" => "app-yes",
            "427" => "app-no",
        ];
        break;

    }

        $result = [];

        foreach ($options as $key => $option) {
            if (isset($map[$option])) {
                $result[] = $map[$option];
            } else {
                throw new \Exception("Invalid value");
            }
        }

        return $result;
    }

    public static function dpq($q)
    {
        $string = dpq($q, true);
        return preg_replace("/\\{|\\}/", "", $string);
    }

    public static function me($uuid)
    {
        $entity = NodeFactory::abstractFactory($uuid);

        return new JsonResponse([
            'data' => [
                'director' => $entity->isDirector(UserFactory::currentUser()->entity)
            ]
        ]);
    }
}
