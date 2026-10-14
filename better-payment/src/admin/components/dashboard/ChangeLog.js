import { __ } from "@wordpress/i18n";
import ChangeLogHeader from "../../svg/ChangeLogHeader";
import ChangeLogItems from "../../svg/ChangeLogItems";
import ChangeLogItems2 from "../../svg/ChangeLogItems2";

const ChangeLog = () => {
  const changes = [
    {
      icon: <ChangeLogItems />,
      title: __("New:", "better-payment"),
      description: __(
        "Payment Form Gutenberg block - Add payment forms anywhere in your content using the block editor",
        "better-payment",
      ),
    },
    {
      icon: <ChangeLogItems />,
      title: __("New:", "better-payment"),
      description: __(
        "User Dashboard Gutenberg block - Give users a centralized view of payments, transactions, subscriptions and analytics.",
        "better-payment",
      ),
    },
    {
      icon: <ChangeLogItems />,
      title: __("Revamped:", "better-payment"),
      description: __(
        "Entire Admin Dashboard with a modern, faster, and more intuitive UI using React",
        "better-payment",
      ),
    }
  ];

  return (
    <div className="bp-col_active-992 bp-custome_col-6_active-992 ">
      <div className="margin-bottom_20 padding-20 bp-card">
        <div className="bp-tag_wrapper">
          <div className="tag">
            <span className="line-height_0">
              <ChangeLogHeader />
            </span>{" "}
            <span className="bp-font_inter fx-4 tag-type">
              {__("New Release", "better-payment")}
            </span>
          </div>
        </div>
        <div className="bp-card_header-wrapper">
          <h4 className="bp-font_ibm fx-3 card-header">
            {__("What’s New In Latest Better Payment?", "better-payment")}
          </h4>
        </div>

        <ul className="payment-box_list">
          <li
            className="bp-font_inter bp-flex payment-box_list-item payment-box_form-gb"
          >
            <span className="line-height_0"></span>

            <span className="bp-font_ibm fx-4 feature-list_sub-title">
              <span className="bp-font_ibm fx-4 feature-list_title">🎉 New:&nbsp;</span><strong><a href="https://betterpayment.co/docs/how-to-create-a-new-campaign" target="_blank" style={{ display: 'inline-block' }}>Campaign Builder</a></strong> - create fundraising campaigns with prebuilt templates & full customization flexibility.
            </span>
          </li>

          {changes.map((change, index) => {
            return (
              <li
                className="bp-font_inter bp-flex payment-box_list-item"
                key={index}
              >
                <span className="line-height_0">{change.icon}</span>

                <span className="bp-font_ibm fx-4 feature-list_sub-title">
                  <span className="bp-font_ibm fx-4 feature-list_title">
                    {change.title}&nbsp;
                  </span>
                  {change.description}
                </span>
              </li>
            );
          })}
        </ul>
        <a
          href={"https://betterpayment.co/changelog"}
          className="bp-font_inter fx-4 bp-btn_2 bp-learn_more-btn"
          target="_blank"
        >
          {__("View Changelog", "better-payment")}
        </a>
      </div>
    </div>
  );
};

export default ChangeLog;
